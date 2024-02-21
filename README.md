This is a demo project for demonstrating the bug/issue of deploying a manually scaled, standard environment PHP service
to App Engine.

Tested with php74, php81, php82 and php83 runtimes (all demonstrate the issue).

## Setup

Create a new GCP project, then deploy the code from a shell
```sh
gcloud config set project [project-name]
gcloud app deploy app.default.yaml --ignore-file=.gcloudignore
```
Once that has completed, confirm that the project is working, by browsing to the project URL
(usually something like [https://\[project-name\].nw.r.appspot.com/](https://\[project-name\].nw.r.appspot.com/))

You can fully test the project by browsing to additional paths
- [https://\[project-name\].nw.r.appspot.com/test/path](https://\[project-name\].nw.r.appspot.com/test/path)
- [https://\[project-name\].nw.r.appspot.com/_ah/stop](https://\[project-name\].nw.r.appspot.com/_ah/stop)

Note that all paths respond with http code 200. The `/_ah/stop` path is special and the response body is 2 bytes ("OK").
All other paths have a response body containing HTML with a reference to the current path (53 bytes plus the length of
the path).

## Re-deploy (What is happening)

The bug/issue arises during subsequent deployments.

```sh
gcloud app deploy app.default.yaml --ignore-file=.gcloudignore
```

When the deployment is almost complete, the script starts an instance with the new code (target version), sets the
traffic split to the new instance, and then stops the old instance.

```
Setting traffic split for service [default]...done.                                                                                                                                                                                    
Stopping version [[project-name]/default/[target-version]].
```

It's this call to stop the instance that is broken, and will show as a 500 http code response (2 bytes) in the logs.

## Manually stopping the instance (What I expect to happen)

If you manually stop an instance, the logs show that the request to `/_ah/stop` is successful (200) and has a much
larger response body (169 bytes in my experiments).

## Summary

Because this is running in the standard environment, when stopping an instance the request to `/_ah/stop` should be
intercepted by the instance at a low level (where it is expected to terminate running tasks like nginx), and not reach
the deployed PHP code.

When an instance is stopped by the deployment script, this request to `/_ah/stop` is not getting intercepted, is
reaching the deployed PHP code, and as such is not performing the required shutdown, resulting in a 500 response code.

When manually stopping an instance, the request to `/_ah/stop` is correctly being intercepted by the instance before
reaching deployed PHP code
