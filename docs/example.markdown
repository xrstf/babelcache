## Examples

### Autoloader

BabelCache comes with its own autoloader. To use it, just include the
`Autoload.php`. It's also possible to use one of the many standard library
loaders, since BabelCache follows the [Proposal](http://groups.google.com/group/php-standards/web/psr-0-final-proposal?pli=1)
by the PHP Standards Working Group.

### Factory

BabelCache only offers an abstract factory. This means you have to implement
your own factory, because the library needs some data specific to your use-case.

A simple factory could look like this:

~~~php~~~
class TestFactory extends BabelCache_Factory {
	protected function getCacheDirectory() {
		$dir = 'C:/tmp/fscache';
		if (!is_dir($dir)) mkdir($dir, 0777);
		return $dir;
	}
}
~~~

Use the factory like this:

~~~php~~~
$factory = new TestFactory();
$cache   = $factory->getCache('BabelCache_Filesystem');
~~~

### Usage

BabelCache support storing, retrieving and deleting values. The interface is
pretty simple (pseudo code):

~~~
interface BabelCache_Interface {
	public static boolean isAvailable();

	public mixed set(string $namespace, string $key, mixed $value);
	public mixed get(string $namespace, string $key, mixed $default = null);

	public boolean delete(string $namespace, string $key);
	public boolean exists(string $namespace, string $key);
	public boolean flush(string $namespace, boolean $recursive = false);

	public boolean lock(string $namespace, string $key, int $duration = 1);
	public boolean unlock(string $namespace, string $key);
	public mixed waitForObject(string $namespace, string $key, mixed $default = null, int $maxWaitTime = 3, int $checkInterval = 50);
}
~~~

~~~php~~~
$factory = new TestFactory();
$cache   = $factory->getCache('BabelCache_Filesystem');

// set three values
$cache->set('my.namespace', 'mykey', 42);
$cache->set('my.namespace', 'anotherkey', 23);
$cache->set('my', 'muh', 3.14);

// getting them back
$cache->get('my', 'muh'); // 3.14

// delete elements
$cache->delete('my', 'muh');
$cache->get('my', 'muh', 'mydefault'); // 'mydefault'

// re-add
$cache->set('my', 'muh', 3.14);

// flush partially
$cache->flush('my.namespace'); // mykey and anotherkey become unavailable
$cache->get('my', 'muh');      // still 3.14

// re-add
$cache->set('my.namespace', 'mykey', 42);
$cache->set('my.namespace', 'anotherkey', 23);
$cache->set('my', 'muh', 3.14);

// flush partially
$cache->flush('my');
$cache->exists('my', 'muh'); // false
~~~

You can store arbitrary elements (with the exception of resources). BabelCache
will always respect their types, so that when you store an int, you will get an
int back.
