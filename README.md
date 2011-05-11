uSettings
=========

Simple parameter holder that serializes to db backend
-----------------------------------------------------

Key/Value holder with a database backend. Essentially this is a parameter
holder that serializes to a database.

In addition to simply storing unrelated key/value pairs it is possible to
specify a group id for a group of key/value pairs to associate them.

This can be helpful if a set of key/value pairs make up a configuration of an
object that need to be retrieved as a complete set.

A setting entry consists of the key, value, type and the group.

 * Key is the name of the key
 * Value is the value assigned to the key
 * Type is the php type of the value (string, integer, boolean, double)
 * Group is the group specifier to tie multiple entries together
