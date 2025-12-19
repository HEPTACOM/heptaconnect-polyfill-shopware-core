# heptacom/heptaconnect-polyfill-shopware-core

This package is a fork of `shopware/core:6.4.20.2`.
Most of the code is removed.
What remains is a barebones package, containing only code that is used by a `heptacom/heptaconnect-*` package.

Composer dependencies are raised, removed or loosened in order to allow upgrading transitive dependencies.
This is necessary, because the original `shopware/core` package has strict constraints that don't allow upgrades.
Over time, some transitive dependencies have accured security vulnerabilities, so upgrading them is essential.

In addition, this allows for a smaller vendor directory.
It also allows for removal of obsolete services and database tables.

DO NOT INSTALL THIS IN A SHOPWARE PROJECT!
