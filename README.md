BF2PHP is currently just a short and simple PHP wrapper for communication with a BF2 or BF2142 game server, using the RCON protocol. It also has support for some extended functions added via BF2CC.

## How the RCON protocol works

The BF2/BF2142 RCON protocol is a plain-text protocol carried over a single TCP connection to the game server's RCON port. A session has three phases: connect, authenticate, then a request/response loop. `BF2RConBase` implements all of it; everything else in this library is built on top of it.

### 1. Connect and handshake

After the TCP connection is opened the server speaks first, sending two newline-terminated lines:

1. A version banner, which the client keeps as the server version.
2. A digest seed line. The seed is the part of the line after the 17-character `### Digest seed: ` prefix, and is different for every connection.

During the handshake, lines are **bare**: they are terminated by `\n` in both directions, with no other framing.

### 2. Authenticate

The password is never sent in the clear. The client hashes the seed together with the password and sends a `login` command:

```
login <md5(seed . password)>
```

The server replies `Authentication successful, rcon ready.` when the digest is accepted. Any other reply means authentication failed and the connection is not usable.

### 3. Commands and responses

Once authenticated, the connection switches to framed messages:

| Direction | Format |
|-----------|--------|
| Client → server | `\x02` + command + `\n` |
| Server → client | response text, terminated by `\x04` |

Responses can span multiple lines, so the `\x04` terminator (rather than a newline) marks the end of a response. The client reads one character at a time until it sees it, then trims the result. Each command gets exactly one response, so requests and responses are strictly sequential.

If the server does not recognise a command it responds with `rcon: unknown command: ...`, which is surfaced as an exception.

### Commands

Most commands are issued through `exec`, which runs a server console command:

```
exec <command> [arguments...]
```

For example:

| Command | Purpose |
|---------|---------|
| `exec sv.getServerName` / `exec sv.setServerName "name"` | Read or change a server setting. Getters take no arguments; setters take the new value. |
| `exec mapList.list` | List the map rotation. |
| `exec admin.kickPlayer <id>` | Perform an admin action. |
| `users` | List connected RCON clients. |

Arguments that may contain spaces, such as names, URLs and messages, are wrapped in double quotes. Multi-line values are sent with `|` in place of newlines.

Replies are free-form text, so `BF2RCon` parses them into PHP values: single values are returned as strings (or booleans for `1`/`0` flags), and list-style replies are split on newlines and then on spaces or tabs into arrays.

### BF2CC extensions

`BF2CC` extends `BF2RCon` with commands added for BF2CC, sent as `bf2cc <subcommand> [arguments...]`. On connect it first registers an admin name (`bf2cc setadminname BF2PHP`) and expects `Admin name set to BF2PHP` in reply. Its replies are tab-separated records, with `\r` between players and `\r\r` between chat messages, which are parsed into arrays:

| Command | Returns |
|---------|---------|
| `bf2cc si` | Server details: server, map, player counts, team tickets and elapsed/remaining time. |
| `bf2cc pl` | Detailed player list, including scores, team/squad and ping. |
| `bf2cc serverchatbuffer` | Recent server chat. |
| `bf2cc sendserverchat <message>` | Send a chat message to the server. |
| `bf2cc switchplayer <id> 0` | Switch a player to the other team. |

### Class overview

| Class | Role |
|-------|------|
| `BF2RConBase` | Socket handling, handshake, authentication and the framed `query()` primitive. |
| `BF2RCon` | Standard RCON commands wrapped as `get_*` / `send_*` methods. |
| `BF2CC` | Extended BF2CC commands, built on `BF2RCon`. |
