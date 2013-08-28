CREATE TABLE babelcache (
	keyhash VARCHAR(50),
	payload MEDIUMBLOB,
	PRIMARY KEY (keyhash)
) ENGINE = MyISAM;
