# Tualo Office - Systemfiles

A Package for storing a files in the database for clustered systems

Set the `./tm configuration --section systemfiles --key useDatabase --value 0` 
to serve files directly through their filesystem callbacks instead of reading or writing the
database cache. This is useful for stage systems where filesystem changes
should be made available to a limited group of users. The callbacks remain
responsible for locating and reading the files.


## Lizenz

MIT License - siehe LICENSE Datei für Details

## Autor

Thomas Hoffmann - thomas.hoffmann@tualo.de
