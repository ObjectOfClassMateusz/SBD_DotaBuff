CREATE TABLESPACE mateusz_tablespace DATAFILE 'mateusz_tablespace.dat' SIZE 10M AUTOEXTEND ON;
CREATE TABLESPACE dominik_tablespace DATAFILE 'dominik_tablespace.dat' SIZE 10M AUTOEXTEND ON;

CREATE USER mateusz_developer IDENTIFIED BY mateusz_haslo QUOTA UNLIMITED ON mateusz_tablespace;
GRANT CREATE SESSION TO mateusz_developer;
GRANT CREATE TABLE TO mateusz_developer;
GRANT CREATE SEQUENCE TO mateusz_developer;
GRANT CREATE PROCEDURE TO mateusz_developer;
CREATE USER dominik_developer IDENTIFIED BY dominik_haslo QUOTA UNLIMITED ON dominik_tablespace;
GRANT CREATE SESSION TO dominik_developer;
GRANT CREATE TABLE TO dominik_developer;
GRANT CREATE SEQUENCE TO dominik_developer;
GRANT CREATE PROCEDURE TO dominik_developer;


COMMIT;
