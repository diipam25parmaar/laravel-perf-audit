# FAQ

Q: Will this automatically add indexes to production DB?
A: No. The package generates migration stubs which must be reviewed and run by a developer.

Q: Does it detect composite indexes?
A: The analyzer can suggest single-column indexes. Composite index detection is a future improvement.

Q: Which DBs are supported for introspection?
A: MySQL and PostgreSQL are supported for index introspection. Other drivers will skip introspection.
