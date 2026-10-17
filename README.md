# TeamX Islandora — Local Contexts integration

A team project integrating Local Contexts labels into an Islandora/Drupal digital repository. The custom module fetches and renders contextual labels associated with repository items.

## My contribution — Tomiwa Adewumi

The repository history records my work on cache configuration and controller changes, test structure, and module installation behavior:

- [Cache setup, controller changes and test structure](https://github.com/Tomiwa-Adewumi/TeamX_Islandora/commit/213e8886)
- [Module update](https://github.com/Tomiwa-Adewumi/TeamX_Islandora/commit/13e870fc)
- [Installation UUID fix PR](https://github.com/Tomiwa-Adewumi/TeamX_Islandora/pull/28)

The related standalone module also includes my [Twig template work](https://github.com/EmmanuelOgbole/local_contexts_integration/commit/d8ea522ede1fd0332b8cbe5a38d9ebe3dc11e963) and [label-display JavaScript](https://github.com/EmmanuelOgbole/local_contexts_integration/commit/f6431b44c264794988c5504824c666b74afb06c4). These links distinguish individual contributions from the team's combined work.

## Where to start

Custom implementation: [`local_contexts_integration`](drupal/rootfs/var/www/drupal/web/modules/custom/local_contexts_integration).

The deployment scaffold comes from Islandora DevOps' `isle-site-template` and `islandora-starter-site`. It is upstream infrastructure, not code authored entirely by this project team. Original setup documentation is preserved in [docs/upstream-setup.md](docs/upstream-setup.md); deployment commands and customization guidance are in [README.template.md](README.template.md).

## Verification and limitations

The module contains unit, kernel and functional test directories. They require a configured Drupal/Islandora environment and its Composer dependencies; this repository's presence of tests is not a claim that all integration tests currently pass. Use a disposable local environment when evaluating the deployment scaffold. No production performance or adoption metrics are claimed.
