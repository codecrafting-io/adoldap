# AdoLDAP

[![Latest Stable Version](https://poser.pugx.org/codecrafting-io/adoldap/v/stable)](https://packagist.org/packages/codecrafting-io/adoldap)
[![License](https://poser.pugx.org/codecrafting-io/adoldap/license)](https://packagist.org/packages/codecrafting-io/adoldap)
[![Downloads](https://poser.pugx.org/codecrafting-io/adoldap/downloads)](https://packagist.org/packages/codecrafting-net/adoldap)

## Summary

- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Configuration](#configuration)
  - [Domain Information](#domain-information)
  - [Connection Configuration](#connection-configuration)
- [Searching](#searching)
  - [Query Builder](#query-builder)
  - [Search Dialects](#search-dialects)
- [Handling Data](#handling-data)
  - [Models & Column Map Attributes](#models--column-map-attributes)
  - [Special Attributes](#special-attributes)
  - [Paging Data](#paging-data)
  - [After Fetch Callback](#after-fetch-callback)
- [Comming Soon](#comming-soon)

 > :warning: **WARNING:** :warning: This library still on alfa, so testing is on the way and newer versions may break backwards compability.

The AdoLDAP is a small PHP library to seamless search and authenticate on the Active Directory with ADO and LDAP. In short terms it provides the following benefits (:star::star::star::star::star:):

- :star: Seamless way to connect to Active Directory, not requiring a single configurtion, not even a server if you wanted.
- :star: A nice semantic syntax that is easy/reusable/fun to use. You can use pre built searchs to find Users, Computers and Groups, handling them as object Model, with a easy human readable get/set syntax.
- :star: A fluent QueryBuilder having support for both [LDAP](https://docs.microsoft.com/en-us/windows/win32/adsi/ldap-dialect) and [SQL](https://docs.microsoft.com/en-us/windows/win32/adsi/sql-dialect) dialects.
- :star: Tools to discover information about you current env, regarding to the available Domain Controllers, connected DCs, main DCs, domain name etc.
- :star: Native PHP data type handling. No strangeous and obscure `VARIANT` objects for returned values, with a nice Iterator interface to loop through objects.

## How it works

[TOP](#adoldap)

The **main feature** that AdoLDAP provides is a **seamless way to authenticate on AD** with LDAP using the current security context of the thread in execution. This is a feature that is implemented by the [**ADODB Active Directory Interface**](https://docs.microsoft.com/en-us/windows/win32/adsi/searching-with-activex-data-objects-ado), which can be used through **COM objects** whithin PHP language (or any COM aware language). Usually this means if the current user are logged on a domain, and this user have permission to search on AD (which likely will), the ADODB don't require especific cridentials to connect.

In another words, now you can create web applications that can search through LDAP without the need of a especific read/write user to connect. For example, you can have use [**Windows Authentication**](https://docs.microsoft.com/en-us/iis/configuration/system.webserver/security/authentication/windowsauthentication/) setup and take the advantage of a seamless search information about the current authenticated user. You also can test on your local machine even not having Windows Authentication.

## Requirements

[TOP](#adoldap)

- Windows environtment.
- PHP >= 7.1 64bits
- COM PHP extension enabled
- [Composer](https://getcomposer.org/)

The AdoLDAP only works on Windows, because COM extensions is the way to use ADODB, since ADO is a Windows only. To install AdoLDAP use the following command:

```sh
composer require codecrafting-io/adoldap
```

## Configuration

[TOP](#adoldap)

### Domain Information

[TOP](#configuration)

Like was said you can search providing no information to connect. This is due to the feature of ADODB that allows connections provding zero information about, credentials or host to connect. If you provide no information about the host or the baseDn to be used to connect, the library will likely connect to the user `logonDomainController`, which is the domain controller host that the user was last connected.

To understant what domain informations are available, use the following code:

```php
try {
    $ad = new AdoLDAP();
    dump($ad->info());
} catch(\Exception $e) {
    dump($e);
}
```

The table below provides the information for each attribute returned:

Attribute | Type | Description
------------ | ------------ | -------------
domain | string | The domain cname
domainName | string | The domain prefix for entries like a user, ex: DOMAIN_NAME\user
defaultNamingContext | string | The default naming context, e.g. the default BASE DN.
logonDomainController | string | The last DC that the current user was logged
machineDomainController | string | The last DC that the current machine was connected
primaryDomainControllers | array | The primary DCs for the domain
domainControllers | array | All available DCs

### Connection configuration

[TOP](#configuration)

Even not beign necessary to provide a single information to connect, is recommended to do it due to consistency of the search results and specially for performance. Is **recommeded** to provide a BASE_DN (you can use the `defaultNamingContext`) and for servers is preferreable to use the `primaryDomainControllers`, to a faster connection. So after you inspect the values returned by the code above, use them to connect like in the code below:

```php
try {
    //Minimal recommended configuration
    $config = [
        'host' => 'server01.mydomain.com', //use a primaryDomainController
        'baseDn' => 'DC=MYDOMAIN,DC=COM',
    ];
    $ad = new AdoLDAP($config); //auto connected
} catch(\AdoLDAPException $e) {
    dump($e);
}
```

The table below provides the information for each configuration:

Config | Type | Default | Description
------------ | ------------ | ------------- | -------------
host | string | null | The server host address to connect
port | int | `DialectInterface::PORT` | The port to use for connecting to your host.
dialect | string | `LDAPDialect::class` | The dialect class to use for the ADO LDAP search and bind. You can also use `SQLDialect::class`
baseDn | string | `DialectInterface::ROOT_DN` | The base distinguished name of your domain. Use ROOT_DN to discover the defaultNamingContext.
username | string | null | The username to connect to your hosts with.
password | string | null | The password that is utilized with the above user.
ssl | bool | false | Whether or not to use SSL when connecting to your host.
autoConnect | bool | true | Whether or not to automaticly connect with the LDAP Provider.
timeout | int | 30 | Timeout of connection execution in seconds
pageSize | int | 1000 | Maximum number of objects to return in a result set page, see <https://docs.microsoft.com/en-us/windows/win32/adsi/retrieving-large-results-sets>
checkConnection | bool | false | Whether or not to check connection execution on bind
parser | string | `Parser::class` | The data parser of a result set.

## Searching

[TOP](#adoldap)

There are two ways to search, using RAW queries or building through the `QueryBuilder`.

Searching using RAW queries:

```php
try {
    $config = [
        'host' => 'server01.mydomain.com', //use a primaryDomainController
        'baseDn' => 'DC=MYDOMAIN,DC=COM',
    ];
    $ad = new AdoLDAP($config);
    $ad->search()->query("<LDAP://server01.mydomain.com/DC=MYDOMAIN,DC=COM>;(&(objectCategory=user)(sAMAccountName=jdoe));sAMAccountName,name");
} catch(\AdoLDAPException $e) {
    dump($e);
}
```

Searching using `QueryBuilder`:

```php
try {
    $config = [
        'host' => 'server01.mydomain.com', //use a primaryDomainController
        'baseDn' => 'DC=MYDOMAIN,DC=COM',
    ];
    $ad = new AdoLDAP($config);
    $ad->search()->whereEquals('objectCategory', 'user')->findBy('sAMAccountName', 'jdoe');
} catch(\AdoLDAPException $e) {
    dump($e);
}
```

### Query Builder

[TOP](#searching)

The query builder allows you to easily create queries using both LDAP and SQL Dialects. The main class `AdoLDAP` provides a `search` method that is a new instance of a `SearchFactory`. The `SearchFactory` can setup a new `QueryBuilder` to construct a search. You can use the `newQuery` method of `SearchFactory` to build a `QueryBuilder`, but you also can use any available method of `QueryBuilder` due to the magic methods present on `SearchFactory`, like the example below:

```php
$ad->search()->newQuery()->whereEquals('objectCategory', 'user')->findBy('sAMAccountName', 'jdoe');

//OR

$ad->search()->whereEquals('objectCategory', 'user')->findBy('sAMAccountName', 'jdoe');
```

The `SearchFactory` also provides pre built or scoped searchs, to facilitate search users, computers and groups.

```php
$ad->search()->users();

//EQUALS

$ad->search()->whereEquals('objectCategory', 'user');
```

```php
$ad->search()->user('jdoe', ['name']);

//EQUALS

$ad->search()->whereEquals('objectCategory', 'user')->firstBy('sAMAccountName', 'jdoe', ['name']);
```

Summarizing, the `QueryBuilder` is compound by methods to construct the selection of attributes and conditional clausules, to search from a sepecific source, using either the `LDAPDialect` or `SQLDialect`. You build a `SELECT` with the `select` method, define the clausules with the `where` methods, having the possibility to use either the `LDAPDialect` or `SQLDialect`. You can also change and set a specific `BASE_DN` with the `from` method.

```php
$ad->search()
    ->select(['name'])
    ->from('DC=MYDOMAIN,DC=COM')
    ->whereEquals('objectCategory', 'user')
    ->orWhere('objectCategory', 'computer')
    ->get();
```

**NOTE:** Is not necessary to define a base dn using from, because the value provided in configuration is used by default.

### Search Dialects

[TOP](#searching)

The `QueryBuilder` can search using both the [LDAP](https://docs.microsoft.com/en-us/windows/win32/adsi/ldap-dialect) and [SQL](https://docs.microsoft.com/en-us/windows/win32/adsi/sql-dialect) dialects. You can configure the dialect on the configuration, like the example below:

```php
try {
    $config = [
        'host' => 'server01.mydomain.com',
        'baseDn' => 'DC=MYDOMAIN,DC=COM',
        'dialect' => SQLDialect::class
    ];
    $ad = new AdoLDAP($config);
} catch(\AdoLDAPException $e) {
    dump($e);
}
```

The default dialect is `LDAPDialect`. You can have your own implementation of both dialects by implementing a `DialectInterface`.

## Handling Data

[TOP](#adoldap)

Once you create your search, the data is retrivied by a `ResultSetIterator`. Using *ADODB* the result data is returned by a [**RecordSet**](https://docs.microsoft.com/en-us/sql/ado/reference/ado-api/recordset-object-ado?view=sql-server-ver15) which have similar function as a cursor, or a iterator. The `ResultSetIterator` implements the native PHP classes `SeekableIterator` and `Countable`, so in this way it's possible to simply loop through like was `array` on a `foreach`.

```php
$users = $ad->search()->users()
            ->select(['sAMAccountName', 'name', 'thumbnailPhoto', 'mail'])
            ->whereMemberOf('CN=AWESOME GROUP,DC=MYDOMAIN,DC=COM')->get();

foreach($users as $user) {
    echo $user->sAMAccountName . '<br>';
    echo $user->name . '<br>';
    echo $user->thumbnailPhoto . '<br>';
    echo $user->mail . '<br>';
}
```

Each position of a result set is retrievied by the `current` method of a `ResultSetIterator`. The data is typically returned as a `Entry`. A `Entry` holds all attributes returned by the search and expose them as get/set magic attributes, like in the example above, or you can also use `getAttribute` and `setAttribute` to get the desired values. If a attribute does not exists a `null` value will be returned instead.

### Models & Column Map Attributes

[TOP](#handling-data)

Most of the searchs actually returns one of the `Models`, could beign a `User`, `Computer` or `Group`. The `Model` extends a `Entry` by provinding a human readeable get/set methods for the "default attributes", that enhances and facilitates the handling of certain values. If you find particular hard to understand the meaning or just don't known the available main attributes for objects on LDAP, the `Model` provides a `COLUMN_MAP` that maps the most important attributes of the corresponding AD object, to the more "human readeable" names. For example take a look to the `COLUMN_MAP` of a `User`:

```php
const COLUMN_MAP = [
    'objectclass'           => 'objectclass',
    'dn'                    => 'distinguishedname',
    'account'               => 'samaccountname',
    'firtname'              => 'givenname',
    'name'                  => 'name',
    'workstations'          => 'userworkstations',
    'mail'                  => 'mail',
    'jobtitle'              => 'description',
    'jobrole'               => 'title',
    'address'   => [
        'street',
        'postalcode',
        'st',
        'l',
        'co'
    ],
    'mailboxes'             => 'msexchdelegatelistbl',
    'mobile'                => 'mobile',
    'phone'                 => 'telephoneNumber',
    'department'            => 'department',
    'departmentcode'        => 'extensionAttribute1',
    'memberOf'              => 'memberOf',
    'company'               => 'company',
    'photo'                 => 'thumbnailphoto',
    'passwordlastset'       => 'pwdlastset',
    'passworderrorcount'    => 'badpwdcount',
    'passworderrortime'     => 'badpasswordtime',
    'lastlogin'             => 'lastlogontimestamp',
    'lockouttime'           => 'lockouttime',
    'createdat'             => 'whencreated',
    'objectguid'            => 'objectguid',
    'objectsid'             => 'objectsid'
];
```

So in this way, the `User` model provides a list of get/set attributes using the mapped names, but if you prefer to use the original name scheme, you can stil use the get/set magic attributes or the `getAttribute` and `setAttribute` methods.

```php
$users = $ad->search()->users()
            ->select(['sAMAccountName', 'name', 'thumbnailPhoto', 'mail'])
            ->whereMemberOf('CN=AWESOME GROUP,DC=MYDOMAIN,DC=COM')->get();

foreach($users as $user) {
    echo $user->getAccount();

    //EQUALS

    echo $user->sAMAccountName;
}
```

The available mapped attributes are physically present on the `Model` class to facilitate the auto complete features of IDEs. The `Model` can also provides special treatment to facilitate the usage of certains attributes, like a user photo.

```php
$users = $ad->search()->users()
            ->select(['sAMAccountName', 'name', 'thumbnailPhoto'])
            ->whereMemberOf('CN=AWESOME GROUP,DC=MYDOMAIN,DC=COM')->get();

foreach($users as $user) {
    echo $user->getAccount() . '<br>';
    echo $user->getName() . '<br>';
    echo $user->getHtmlPhoto(['class' => 'profile-picture']) . '<br>';
}
```

The `getHtmlPhoto` returns a `IMG` HTML tag, already containing the class `profile-picture`, using the `src` as base64 string representation of the image. For the photo attribute you can also get just the base64 or the raw binary string or even save the photo to a file, with the respective methods `getPhoto`, `getRawPhoto`, `savePhoto`.

You can also use the mapped attributes in a `QueryBuilder` selection.

```php
$attributes = SearchFactory::translateAttributes(User::COLUMN_MAP, ['accountName', 'name', 'photo', 'mailboxes']);

$ad->search()->users()
            ->select($attributes)
            ->whereMemberOf('CN=AWESOME GROUP,DC=MYDOMAIN,DC=COM')->get();
}
```

If you use the `SearchFactory` to search a single `Entry`, the `user`, `computer` and `group` methods also provides a parameter that already translate the attributes, in fact the default behavior of these methods is to translate attributes.

```php
$ad->search()->user('jdoe', ['accountName', 'name', 'photo', 'mailboxes']);

//EQUALS

$ad->search()->user('jdoe', ['sAMAccountName', 'name', 'thumbnailPhoto', 'msExchDelegateListBl'], false);
```

If you provide no attributes to those methods, the entire `COLUMN_MAP` will be used instead.

```php
$ad->search()->user('jdoe');

//EQUALS

$ad->search()->user('jdoe', User::getDefaultAttributes(), false);
```

> :warning: **IMPORTANT:** :warning: Is possible to select all attributes, by provinding the value `['*']`, but this is **EXTREMELY DISCOURAGED**, not only by the fact that may have a lot of attributes that possibily won't be used, but specially to performance reasons. When you use a query, using the wildcard `*` the ADODB returns the ADSPATH, which is the full distinguished name of the object, forcing the library to resolve them by binding directly to the object using `COM`, which is EXTREMELY slow even for a single object. Normally searching for a entry takes arround 200-400ms, but binding to the object can take 4s. So only use for test purposes.

### Special Attributes

[TOP](#handling-data)

In addition to the `Model`, some attributes are handled as objects, like the `DistinguishedName`, `OS`, `Address` and `ObjectClass`.

#### DistinguidedName

The `DistinguishedName` class as the name says handles the distinguished name, providing easy way to extract components and parts inside the name.

```php
$dn = new DistinguishedName('CN=AWESOME GROUP,DC=MYDOMAIN,DC=COM');
echo $dn->getName() . '<br>'; //Container Name - AWESOME GROUP
echo $dn->getPath() //Whole Path - CN=AWESOME GROUP,DC=MYDOMAIN,DC=COM
```

#### ObjectClass

The `ObjectClass` is a simple object wrap to the `array` of the `objectClass` attributes. The class also provides the method `getMostRelevant` which is the last and most significative name of a `ObjectClass`. Each `Model` already have a defined `objectClass` that can be obtained by using the static method `objectClass`.

```php
echo User::objectClass()->getMostRelevant() //outputs user
```

#### Address

The `Address` is a simple POJO object for the all address related attributes present on `User` entry. You can note that the `COLUMN_MAP` maps the `address` as 4 other attributes, which are flatten to compose the selection on the `QueryBuilder`. The Address also have a more clear syntax and name scheme.

```php
$user = $ad->search()->user('jdoe', ['accountName', 'address']);
//EQUALS TO: $ad->search()->user('jdoe', ['sAMAcountName', 'street', 'postalCode', 'st', 'l', 'co']);

echo $user->getAddress()->getCountry();
```

#### OS

The `OS` handles the OS related values of the attributes `operatingSystem` and `operatingSystemVersion`, that can be found on `Computer` entries. The class extracts and splits the data providing methods to retrieve the `name`, `version`, `flavour`, and also ways to easily compare OSs.

```php
$computer = $ad->search()->computer('MACHINE01');
echo $computer->getOS()->getName();
$computer->compareTo($ad->search()->computer('MACHEINE02')) //outputs -1, 0, 1;
```

### Paging Data

[TOP](#handling-data)

The ADODB natively page the results by using the [`RecordSet`](https://docs.microsoft.com/en-us/windows/win32/adsi/searching-with-activex-data-objects-ado), which are managed by the `ResultSetIterator`. You can provide a specific LDAP page size by a value for `pageSize` configuration. The default value is 1000. Nevertheless, the `ResultSetIterator` provides a separate paging using the `getEntries` method, which allows you to define a proper limit/offset.

```php
//Get 10 users offseting 1 page. Returns a array of User objects
$users = $ad->search()->users()
            ->select(User::getDefaultAttributes())
            ->whereMemberOf('CN=AWESOME GROUP,DC=MYDOMAIN,DC=COM')
            ->get()->getEntries(10, 1);
```

### After Fetch Callback

[TOP](#handling-data)

The `ResultSetIterator` also provides a `afterFetch` callback, which allows you to transform the entries every time the `current` method is called.

```php
$user = $ad->search()->user('jdoe')->afterFetch(function($user) {
                return [
                    'name' => $user->getName(),
                    'accountName' => $user->getAccount(),
                    'photo' => $user->getPhoto()
                ];
            })->getEntries();
```

You can set afterFetch multiple times, which will transform the data multiple times by the order that was provided.

## Comming Soon

[TOP](#adoldap)

- Active Record `Model`. For now only search functionality is available.
- Event support.
- More robust `QueryBuilder`.
- Full and complete documentation site.
- Cache support.

## For Last

Thanks for the [Adldap2](https://github.com/Adldap2/Adldap2) for inspiration to create this library.

> Made with :heart: by [@lucasmarotta](https://github.com/lucasmarotta).
