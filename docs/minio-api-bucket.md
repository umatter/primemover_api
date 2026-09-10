# Giving the API its own MinIO bucket

The API's `s3` disk holds files the API itself creates: experiment export zips
(`GET /experiments/{id}/download`) and anything posted to `POST /upload`. Today
it is set to the **local disk inside the php container** (`AWS_MOCK=true`), which
works and needs no credentials, but those files are not replicated and are not
covered by the nightly restic backup that protects MinIO.

This is how to move it onto MinIO properly. It needs MinIO **admin** credentials,
which is why it is written down rather than done for you.

## Why not just reuse what exists

Two credentials already reach the MinIO on this box, and neither is right:

| credential | can | why not |
|---|---|---|
| the API's `s3runner` key | **read only** — verified: `Storage::disk('s3runner')->put()` returns `false` | it is scoped read-only on purpose; the API reads job data, it does not write it |
| the runner's `awsS3` key | read + write on `primemoverrunner` | giving it to the API grants write access to **39 GB** of experimental job data the API has no business writing to, and one key serving two services cannot be revoked for one of them |

Hence a new bucket and a new key, scoped to it.

## 1. Point `mc` at the server

Root credentials are the MinIO container's `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD`
(in the ex63-infra environment, readable by `umatter`).

```bash
docker exec -it ex63-minio-1 mc alias set admin http://127.0.0.1:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD"
```

## 2. Create the bucket

Separate from `primemoverrunner` on purpose: exports the API generates and job
data the runner captures have different owners, different lifetimes and
different access needs.

```bash
docker exec ex63-minio-1 mc mb --ignore-existing admin/primemover-api
```

## 3. A policy scoped to that bucket and nothing else

`ListBucket` is on the bucket ARN; the object verbs are on its contents. No
`s3:*`, and no other bucket named anywhere -- if this key leaks, it reaches
exactly one bucket.

```bash
docker exec -i ex63-minio-1 sh -c 'cat > /tmp/primemover-api-policy.json' <<'JSON'
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": ["s3:ListBucket"],
      "Resource": ["arn:aws:s3:::primemover-api"]
    },
    {
      "Effect": "Allow",
      "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
      "Resource": ["arn:aws:s3:::primemover-api/*"]
    }
  ]
}
JSON

docker exec ex63-minio-1 mc admin policy create admin primemover-api-rw /tmp/primemover-api-policy.json
```

`DeleteObject` is needed: the download pipeline's `CleanUpTempFilesAndFolders`
removes the archive after serving it, and without delete every export would be
kept for ever.

## 4. A user for the API, and only the API

```bash
# pick a secret you do not reuse; 40 chars of base64 is plenty
docker exec ex63-minio-1 mc admin user add admin primemover-api "$(openssl rand -base64 30)"
docker exec ex63-minio-1 mc admin policy attach admin primemover-api-rw --user primemover-api
```

Keep the secret: it is shown once, when you type it.

## 5. Wire it into the API

In `/opt/primemover/primemover_api/.env`:

```
AWS_MOCK=false
AWS_ENDPOINT=http://minio:9000
AWS_BUCKET=primemover-api
AWS_ACCESS_KEY_ID=primemover-api
AWS_SECRET_ACCESS_KEY=<the secret from step 4>
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Three of those are load-bearing in ways their names do not suggest:

- **`AWS_ENDPOINT`** — without it the client addresses real AWS whatever else is
  set. The `s3` disk had no `endpoint` key at all until 2026-09-10, the same
  omission `s3runner` had until 2026-09-08.
- **`http://minio:9000`**, not `s3.qamelab.org` — the public hostname answers
  **403** from a docker bridge address, because the edge allow-list sees a bridge
  IP rather than a tailnet one.
- **`AWS_USE_PATH_STYLE_ENDPOINT=true`** — MinIO serves buckets as a path
  segment; virtual-host style would resolve `primemover-api.minio:9000`, which
  does not exist.

No restart is needed: the source is bind-mounted and `.env` is read per request.

## 6. Check it

```bash
# should return a zip, not a 500
curl -s -o /tmp/exp.zip -w '%{http_code} %{size_download}\n' \
  "http://localhost:8090/api/v1/experiments/<id>/download" -H "Authorization: Bearer $TOKEN"

# and the object should now exist in the bucket
docker exec ex63-minio-1 mc ls admin/primemover-api --recursive | head
```

If the download 500s, the API now says which half failed: since 2026-09-10 a
rejected write throws at the write, naming the disk, rather than surfacing two
steps later as `File not found` from the read-back.

## Rolling back

`AWS_MOCK=true` restores the local disk immediately, with no other change. Files
already written to MinIO stay there.
