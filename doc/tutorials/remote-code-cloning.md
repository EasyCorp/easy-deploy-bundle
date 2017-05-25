Cloning the Application Code on Remote Servers
==============================================

Some deployment strategies clone on the remote servers the application code
hosted on an external repository (e.g. GitHub). Depending on your local and
remote configuration, this process may fail.

This article explains the most common solutions for those problems. The examples
below use GitHub.com, but you can translate those ideas to other Git services,
such as BitBucket and GitLab.

SSH Agent Forwarding
--------------------

**Agent forwarding** is the strategy recommended by this bundle and used by
default. If enabled, remote servers can use your local SSH keys to access other
external services. First, execute this command in your local machine, to verify
that you can access to GitHub web site using SSH:

```bash
$ ssh -T git@github.com

Hi <your-name-here>! You've successfully authenticated, but GitHub does not
provide shell access.
```

Now log in any of your remote servers and execute the same command:

  * If you see the same output, agent forwarding is working and you can use it
    to deploy the applications. This option is enabled by default and can be
    changed with the `useSshAgentForwarding` option in your deployer.
  * If you encounter the error **Permission denied (publickey)**, check that:
    * The local SSH config file has not disabled agent forwarding for that host
      (see [this tutorial][1] for more details).
    * The remote SSH server hasn't disabled agent forwarding (see [this tutorial][2]
      for more details).
    * Read [this GitHub guide][3] to troubleshoot agent forwarding issues.

Deploy Keys
-----------

If you can't or don't want to use SSH agent forwarding, the other simple way to
clone the code on remote servers is using **deploy keys**. They are SSH keys
stored on your remote servers and they grant access to a single GitHub
repository. The key is attached to a given repository instead of to a personal
user account. Follow these steps:

  1. Log in into one of your remote servers.
  2. Execute this command to generate a new key:

     ```bash
     $ ssh-keygen -t rsa -b 4096 -C "your_email@example.com"
     ```

     Press `<Enter>` for all questions asked to use the default answers. This
     makes your key to not have a "passphrase", but don't worry because your
     key will still be safe.
  3. The command generates two files, one of the private key and the other one
     for the public key. The deploy key is the public key. Display its contents
     so you can copy them. For example: `cat ~/.ssh/id_rsa.pub` (you'll see some
     random characters that start with `ssh-rsa` and end with your own email).
  4. Go to the page of your code repository on GitHub and click on the **Settings**
     option (the URL is `https://github.com/<user-name>/<repo-name>/settings`).
  5. Click on the **Deploy keys** option on the sidebar menu.
  6. Click on the **Add deploy key** button, give it a name (e.g. `server-1`)
     and paste the contents of the public key that you copied earlier.
  7. Click on the **Add key** button to add the key and your remote server will
     now be able to clone that specific repository.
  8. If the same server needs access to other repositories, repeat the process
     to add the same public key in other repositories.
  9. If other servers need access to this repository, repeat the process to
     generate keys on those servers and add them in this repository.

Read this guide if you any problem generating the SSH keys:
[Connecting to GitHub with SSH][4].

Other Cloning Techniques
------------------------

If you can't or don't want to use SSH Agent Forwarding or Deploy Keys, you can
use HTTPS Oauth Tokens and even create Machine Users and add them as
collaborators in your GitHub repositories. Read [this guide][5] to learn more
about those techniques.

[1]: local-ssh-config.md
[2]: remote-ssh-config.md
[3]: https://developer.github.com/v3/guides/using-ssh-agent-forwarding/
[4]: https://help.github.com/articles/connecting-to-github-with-ssh/
[5]: https://developer.github.com/v3/guides/managing-deploy-keys/
