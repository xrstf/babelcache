CREATE TABLE "babelcache" (
	"prefix"    VARCHAR(50),
	"namespace" VARCHAR(255),
	"keyname"   VARCHAR(255),
	"payload"   BLOB,
	PRIMARY KEY ("prefix", "namespace", "keyname")
);
