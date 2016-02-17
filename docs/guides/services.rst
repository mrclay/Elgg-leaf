Services
########

Elgg uses the ``Elgg\Application`` class to load and bootstrap Elgg. In future releases this
class will offer a set of service objects for plugins to use.

.. note::

    If you have a useful idea, you can :doc:`add a new service </contribute/services>`!

The ``urls`` service
====================

``elgg()->urls`` returns an implementation of ``Elgg\Services\Urls``, usable for analyzing the site URL or
comparing it to others.
