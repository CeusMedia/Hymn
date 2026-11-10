# Versions

Modules can need Composer packages. On install and update, Hymn checks whether these packages are
installed in the app and whether their versions satisfy the constraint. Installed packages are read
from `vendor/composer/InstalledVersions.php` in the application folder (`application.uri` of the
[Hymn file](hymn-file.md#application)) – or in the current folder, if `application.uri` is not set:

```xml
<relations>
	<needs type="module">Resource_Database</needs>
	<needs type="package" version="^2.0 || ^3.0">ceus-media/common</needs>
</relations>
```

Like in Composer, the constraint can also be appended to the package name:
`<needs type="package">ceus-media/mail:^2.6</needs>`. If both are given, the attribute `version` wins.
Without constraint, any installed version is fine.

| Situation | Result |
|-----------|--------|
| package not installed | error, installation stops |
| version does not satisfy the constraint | error, installation stops |
| development version, eg. `dev-master` or `1.0.x-dev` | note, not checked |
| version not semantic, eg. `2.4.0.1` | note, not checked |
| constraint invalid | error, installation stops |
| no Composer in the app | packages are not checked – shown with `--verbose` |

## Version constraints

Constraints follow the syntax of [Composer](https://getcomposer.org/doc/articles/versions.md).

| Constraint | Meaning |
|------------|---------|
| `1.2.3`, `=1.2.3`, `==1.2.3` | exactly this version |
| `>1.2`, `>=1.2`, `<2.0`, `<=2.0` | comparison |
| `!=1.5.0`, `<>1.5.0` | any version but this |
| `^1.2.3` | `>=1.2.3 <2.0.0` – no breaking changes |
| `^0.3` | `>=0.3.0 <0.4.0` – below 1.0, the minor version counts as major |
| `^0.0.3` | `>=0.0.3 <0.0.4` |
| `~1.2` | `>=1.2.0 <2.0.0` |
| `~1.2.3` | `>=1.2.3 <1.3.0` |
| `1.2.*`, `1.2.x` | `>=1.2.0 <1.3.0` |
| `*` | any version |
| `1.0 - 2.0` | `>=1.0.0 <2.1.0` – a partial upper version includes all its patches |
| `1.0.0 - 2.1.0` | `>=1.0.0 <=2.1.0` |

Combine constraints:

| Combination | Example |
|-------------|---------|
| AND – space, comma or `&&` | `>=1.0 <2.0`, `>=1.0,<2.0` |
| OR – <code>&#124;&#124;</code> or <code>&#124;</code> | <code>^8.1 &#124;&#124; ^9.0</code> |
| both – AND binds stronger | <code>&gt;=1.0 &lt;1.5 &#124;&#124; ^2.0</code> |

Unknown operators or invalid versions are rejected with an error, instead of being ignored.

## Comparing versions

Versions are compared by the rules of [Semantic Versioning 2.0.0](https://semver.org/):

- `1.10.0` is newer than `1.9.0` – numbers are compared numerically,
- a pre-release is older than its release: `1.0.0-beta < 1.0.0`,
- pre-releases are compared part by part: `1.0.0-beta.2 < 1.0.0-beta.11`,
- build metadata is ignored: `1.0.0+5` equals `1.0.0`,
- an exclusive upper bound excludes its pre-releases: `2.0.0-beta` does not satisfy `^1.0` or `<2.0`.

A leading `v`, like in `v1.2.0`, is allowed. Partial versions are completed: `1.2` is `1.2.0`.
