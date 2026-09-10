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

## Which account runs which step

Only step 5 is genuinely account-bound. Steps 1-4 run either way: both accounts
are in the `docker` group, and because the container expands its own environment
neither has to read a secret from the host at all. That is also why the single
quotes matter -- they keep the credentials off the host command line and out of
its history.

| step | user | why |
|---|---|---|
| 1-4 (alias, bucket, policy, user) | **either** | the credentials are expanded inside the container, so no host-side account needs to read them. `umatter` can also read them from `/opt/ex63-infra/.env` directly if you prefer |
| 5 (the API's `.env`) | **`primemover`** | `/opt/primemover/primemover_api/.env` is `primemover:primemover` mode 600; `umatter` cannot read or write it |
| 6 (verify) | either | both can `docker exec` and reach `localhost:8090` |

So the secret minted in step 4 has to cross from one account to the other. Do
not pipe it between shells or echo it into a command line that a history file
will keep. Generate it, hold it yourself, and paste it into the `.env` in the
`primemover` session -- the same handling the MCP bearer tokens get.

## 1. Point `mc` at the server

The root credentials are `MINIO_ROOT_USER` / `MINIO_ROOT_PASSWORD`, interpolated
from `/opt/ex63-infra/.env` into the **container's** environment at compose time.
They are not set in your login shell, so the shell that expands them has to be
the container's -- note the SINGLE quotes:

```bash
docker exec ex63-minio-1 sh -c 'mc alias set admin http://127.0.0.1:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD"'
```

With double quotes your shell expands them first, mc receives two empty strings,
and -- this is the trap -- **`mc alias set` does not validate credentials**. It
answers ``Added `admin` successfully.`` either way, and the failure surfaces one
command later as a bare `Access Denied` from `mc mb`.

Check the alias before going on:

```bash
docker exec ex63-minio-1 mc ls admin      # should list the buckets
```

**If the alias was wrong, re-run from step 2.** Every command in between fails
with the same bare `Access Denied`, and repairing the alias does not retry them
-- so it is easy to fix step 1, carry on from wherever you had got to, and end up
with a policy and a user but no bucket.

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
docker exec ex63-minio-1 mc admin user add admin primemover-api "$(openssl rand -base64 30)"
docker exec ex63-minio-1 mc admin policy attach admin primemover-api-rw --user primemover-api
```

The user's own password is deliberately generated inline and never read: the
API does not use it. Give the API an **access key** under that user instead,
which `mc` prints once, on creation:

```bash
docker exec ex63-minio-1 mc admin accesskey create admin/primemover-api \
    --name "primemover-api (API file storage)"
```

That prints an access key and a secret key -- those two are what go in the
`.env`, not the user's password. Copy them now; they are not retrievable
afterwards.

An access key rather than the user's own credentials, for three reasons: `mc`
displays it (a password generated inline by `$(openssl ...)` is never shown at
all -- an earlier version of this runbook told you to keep a secret the command
had already thrown away), it inherits the policy attached to the user, and it can
be revoked on its own with `mc admin accesskey rm <access-key>` without
disturbing the user or minting anything new.

Run this yourself rather than having an agent run it: the secret is printed to
whatever terminal executes it, and it should be yours.

## 5. Wire it into the API

As `primemover`, in `/opt/primemover/primemover_api/.env`:

```
AWS_MOCK=false
AWS_ENDPOINT=http://minio:9000
AWS_BUCKET=primemover-api
AWS_ACCESS_KEY_ID=<the access key from step 4>
AWS_SECRET_ACCESS_KEY=<the secret key from step 4>
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_DEFAULT_REGION=eu-central-1
```

Each of those exactly ONCE. A duplicate is survivable but treacherous: the last
definition wins, so editing the first occurrence silently does nothing.

`AWS_DEFAULT_REGION` is **required**, even though MinIO ignores its value -- the
SDK refuses to construct a client without one. It is the easiest line to lose
when clearing out an older AWS block, and `s3runner` reads the same variable, so
losing it affects both disks.

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
