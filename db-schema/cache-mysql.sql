CREATE TABLE babelcache (
	prefix    VARBINARY(50),
	namespace VARBINARY(255),
	keyname   VARBINARY(255),
	payload   BLOB,
	PRIMARY KEY (prefix, namespace, keyname)
) ENGINE = MyISAM;
