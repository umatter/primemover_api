# Primemover
Infrastructure overview.
## API: Hetzner, Helsinki Finnland
API server running PHP/MySQL based Laravel Web Application.
<table>
<thead>
<tr>
<td>Title</td>
<td>Description</td>
<td>Notes</td>
</tr>
</thead>
<tbody>
<tr>
<td>Type:</td>
<td>Virtual Private Server</td>
<td></td>
</tr>
<tr>
<td>SSH connection:</td>
<td>ssh root@135.181.37.96 -i ~/.ssh/runner-api</td>
<td></td>
</tr>
<tr>
<td>OS:</td>
<td>Ubuntu 18.04 LTS</td>
<td></td>
</tr>
<tr>
<td>IPv4:</td>
<td>135.181.37.96</td>
<td></td>
</tr>
<tr>
<td>URL:</td>
<td>https://primemover.example/api/v1</td>
<td></td>
</tr>
<tr>
<td>MySQL root:</td>
<td>#And20Qom5F</td>
<td></td>
</tr>
<tr>
<td>MySQL sphinx:</td>
<td>CHANGEME-backend-password</td>
<td></td>
</tr>
<tr>
<td>MySQL primemover:</td>
<td>CHANGEME-backend-password</td>
<td></td>
</tr>
<tr>
<td>Specs:</td>
<td>3vCore | 4GB RAM | 80 GB | 150 GB (scalable)</td>
<td></td>
</tr>
<tr>
<td>Network:</td>
<td>10 Gbps | 20 TB Traffic / Month</td>
<td></td>
</tr>
<tr>
<td>Backup:</td>
<td>Not configured (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Snapshots:</td>
<td>Available (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Expires:</td>
<td>Never, done manually</td>
<td></td>
</tr>
<tr>
<td>SSH Key:</td>
<td>runner-api</td>
<td></td>
</tr>

</tbody>
</table>


## Runner: Infomaniak, Vernier

<table>
<thead>
<tr>
<td>Title</td>
<td>Description</td>
<td>Notes</td>
</tr>
</thead>
<tbody>
<tr>
<td>SSH connection:</td>
<td>ssh ubuntu@195.15.226.18 -i ~/.ssh/runner-b</td>
<td></td>
</tr>
<tr>
<td>Type:</td>
<td>Virtual Private Server</td>
<td></td>
</tr>
<tr>
<td>OS:</td>
<td>Ubuntu 18.04 LTS</td>
<td></td>
</tr>
<tr>
<td>IPv4:</td>
<td>195.15.226.18</td>
<td></td>
</tr>
<tr>
<td>User:</td>
<td>ubuntu</td>
<td></td>
</tr>
<tr>
<td>Password:</td>
<td>CHANGEME-backend-password</td>
<td></td>
</tr>
<tr>
<td>Additional role:</td>
<td>VNC Server</td>
<td></td>
</tr>
<tr>
<td>VNC Password:</td>
<td>CHANGEME</td>
<td></td>
</tr>
<tr>
<td>VNC Viewer:</td>
<td>Staff12%</td>
<td></td>
</tr>
<tr>
<td>VNC Connection:</td>
<td>ssh ubuntu@195.15.226.18 -i ~/.ssh/runner-b -L 5901:127.0.0.1:5901 -C -N -l ubuntu 195.15.226.18
</td>
<td></td>
</tr>
<tr>
<td>Specs:</td>
<td>12vCore | 36GB RAM | 100 GB</td>
<td></td>
</tr>
<tr>
<td>Network:</td>
<td>10 Gbps</td>
<td></td>
</tr>
<tr>
<td>Backup:</td>
<td>Not configured (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Snapshots:</td>
<td>Available (use based fee)</td>
<td></td>
</tr>
<tr>
<td>Expires:</td>
<td>04.03.21 (T-90)</td>
<td></td>
</tr>
<tr>
<td>SSH Key:</td>
<td>runner-b</td>
<td></td>
</tr>
</tbody>
</table>


## API: Metanet, Zürich
Current API runs on a shared hosting environment which is not configured to be accessed over SSH.
<table>
<thead>
<tr>
<td>Title</td>
<td>Description</td>
<td>Notes</td>
</tr>
</thead>
<tbody>
<tr>
<td>URL:</td>
<td>https://siaw.primemover.example/api/v1</td>
<td></td>
</tr>
<tr>
<td>Type:</td>
<td>Shared Hosting</td>
<td></td>
</tr>
<tr>
<td>IPv4:</td>
<td>80.74.128.40</td>
<td></td>
</tr>
</tbody>
</table>

	

