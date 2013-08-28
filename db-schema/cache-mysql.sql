CREATE TABLE babelcache (
	prefix    VARBINARY(50),
	namespace VARBINARY(255),
	keyname   VARBINARY(255),
	payload   MEDIUMBLOB,
	PRIMARY KEY (prefix, namespace, keyname)
) ENGINE = MyISAM;
