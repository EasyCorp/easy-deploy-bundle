Troubleshooting Connection Issues to Remote SSH Servers
=======================================================

EasyDeploy requires working SSH connections to remote servers in order to deploy
and roll back the applications. This article summarizes the most common SSH
issues and proposes some solutions.

You can also add the `-v` option to the `ssh` command to enable its verbose mode
and print debugging messages to help you find connection, authentication, and
configuration problems (e.g. `ssh -v my-server`).

Connection Refused
------------------

### There may be too many simultaneous SSH connections to the server

Check the `MaxSessions` and `MaxStartups` config options of the SSH server.

```ini
# /etc/ssh/sshd_config
# ...

# Defines the maximum number of concurrent unauthenticated connections. Additional
# connections are dropped until authentication succeeds. Default: 10:30:100
# (if there are '10' connections, refuse '30'% of them and increase the drop rate
# linearly until '100' connections are reached and then all are refused)
MaxStartups 10:30:100

# Defines the maximum number of open shell, login or subsystem (e.g. sftp)
# sessions permitted per connection. This mostly affects users with
# multiplexing connections.
MaxSessions 10
```

### The firewall of the remote server may be dropping your SSH connections

Deploying an application requires making lots of SSH connections in a short
period of time. Some firewalls may consider that a suspicious behavior and start
dropping some of your SSH connections.

Permission denied
-----------------

### Remote servers may have disabled connections using public keys

This is the recommended method to connect to remote SSH servers, but it may
have been inadvertently disabled:

```ini
# /etc/ssh/sshd_config
# ...

# set this option to 'yes' to enable authentication using public keys
PubkeyAuthentication yes
```

### Remote servers may have disabled connections using passwords

Connecting to remote SSH servers with usernames and passwords is discouraged in
favor of encryption keys. However, if you are still using passwords, make sure
that servers allow to connect to them using passwords:

```ini
# /etc/ssh/sshd_config
# ...

PasswordAuthentication yes
```

### Remote servers may have disabled root login

Login (and deploying) as the `root` user is a bad security practice. That's why
most servers disable root logins. If you still want to connect as `root`, you
must define the `PermitRootLogin` option:

```ini
# /etc/ssh/sshd_config
# ...

# possible values are 'yes', 'no', 'prohibit-password', 'without-password'
# and 'forced-commands-only' (default: 'prohibit-password')
PermitRootLogin yes
```

### Remote servers may have banned your user or group

SSH servers can define a list of allowed/denied users and groups. Check that
the user connecting to the server is allowed or at least not denied:

```ini
# /etc/ssh/sshd_config
# ...

# these four options are processed in the following order and they accept both
# full user/group names and patterns
DenyUsers ...
AllowUsers ...
DenyGroups ...
AllowGroups ...
```

Connection is Slow
------------------

### DNS resolution may be enabled

Disable the option that makes the remote SSH server to resolve host names.

```ini
# /etc/ssh/sshd_config
# ...

# If set to 'yes', the SSH server looks up the remote host name to check that the
# resolved host name for the remote IP address maps back to the very same IP address.
UseDNS no
```

### Compression may be disabled for the connection

As explained [in this tutorial][1], the `Compression` option should be disabled
for fast Internet connections, but it must be enabled for slow connections to
improve the perceived performance significantly.

Remote Servers Can't Clone the Repository Code
----------------------------------------------

### ForwardAgent may be disabled by the remote server

EasyDeploy enables by default the `ForwardAgent` option to let your remote
servers clone the repository code from external sites such as GitHub. However,
it's not enough to enable this option in your local SSH config file or in the
`ssh` command used to connect to the server.

Check that the `AllowAgentForwarding` option in the server is set to `yes`.

```ini
# /etc/ssh/sshd_config
# ...

AllowAgentForwarding yes
```

[1]: local-ssh-config.md
