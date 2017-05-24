Creating a Local SSH Configuration File
=======================================

If you connect to lots of different servers using SSH, you may end up executing
complex commands like the following:

```bash
$ ssh user1@123.123.123.123 -p 22123
$ ssh deployer@host23.example.com
$ ssh client2@staging.example.com -p 22001
```

Remembering the user, hostname or IP and port number for each SSH connection is
not easy. Luckily, SSH lets you store this information in a configuration file
so you can connect to those servers using easy-to-remember names:

```bash
$ ssh client1
$ ssh client2
$ ssh client2-staging
```

Defining the SSH Server Configuration
-------------------------------------

Let's consider that you execute the `ssh user1@123.123.123.123 -p 22123` command
to connect to the server of your client called `client1`.

**Step 1.** Edit (or create if it doesn't exist) a file called `~/.ssh/config`
(this is a file called `config` in a hidden directory called `.ssh/` inside your
user directory; for example `/Users/jane`).

**Step 2.** Add the configuration for the server using this format:

```ini
Host client1
  HostName 123.123.123.123
  User user1
  Port 22123
```

**Step 3.** Save the changes, close the `~/.ssh/config` file and test the new
config executing the following command: `ssh client1`. You should be connected
to the server of your client.

**Step 4.** Now repeat the above steps to add the config of the rest of the
servers.

Additional Config Options
-------------------------

The above example used the `HostName`, `User` and `Port` options, but SSH config
files can define a lot of other options. These are the most common:

```ini
Host client1
    # ...

    # if set to 'yes', SSH compresses any communication between your local computer
    # and the remote server. At first it looks like a good idea, but it should be
    # enabled only for slow connections. On fast connections, this setting will
    # actually make your connection slower because of the compression overhead.
    Compression yes

    # some people don't recommend using this option because it may be dangerous
    # in some scenarios. In any case, there's no need to define it in this config
    # file because you can enable/disable it using the deployer config file.
    ForwardAgent yes

    # it defines the timeout interval (in seconds) after which, if no data has been
    # received from the server, ssh will send a message to the server to maintain the
    # connection alive (it's useful to avoid connection drops because of inactivity)
    ServerAliveInterval 60

    # it defines the path to the private key used to connect to the server. By
    # default it uses one of these files: ~/.ssh/{id_dsa,id_ecdsa,id_rsa}
    # Define this option only for advanced scenarios where you use different auth
    # keys per remote server.
    IdentityFile /path/to/ssh_id_rsa or /path/to/ssh_id_dsa or /path/to/ssh_id_ecdsa
```

See the [full list of SSH configuration options][1].

[1]: http://man.openbsd.org/ssh_config
