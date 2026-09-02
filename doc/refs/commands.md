# CLI commands

Artisan commands shipped by Quest. They help you **create** references, **find** them later, and **inspect** the method behind a `ref`.

The tracker sees:

- classes listed in `routes/quest.php`
- classes / directories passed to `Quest::spawn` / `Quest::spaw` in the files listed in `config/quest.php` → `base_routes` (default: `routes/web.php`, `routes/api.php`)

If you register Quest from another file, add that path to `base_routes`.

---

## `quest:generate-ref`

Generate a reference string to paste into `#[QuestSpaw(ref: …)]`.

```bash
php artisan quest:generate-ref
php artisan quest:generate-ref 16
php artisan quest:generate-ref --uuid
```

| Argument / option | Role |
| --- | --- |
| `length` | Character count for a random string (default `36`). Ignored with `--uuid`. |
| `--uuid` | Generate a UUID v4 instead of a random string. |

You are not required to use this command. Any string without `/` is a valid `ref`. A readable prefix plus a generated suffix is easier to live with:

```
forest.tree.NAhLlRZW3g3Fbh30dZ
```

---

## `quest:track-ref`

Show where a `ref` is implemented: class, method signature, attribute arguments, file.

```bash
php artisan quest:track-ref forest.tree
php artisan quest:track-ref RrOWXRfKOjauvSpc7y
```

| Argument | Role |
| --- | --- |
| `ref` | Exact reference to look up. |

Possible outcomes:

- match — prints namespace, `#[QuestSpaw]` arguments, and the method signature (including PHPDoc when present)
- unmatched — no method with that `ref` in the discovered classes
- empty register — no classes discovered (check `routes/quest.php` and `base_routes`)

This is the command to run when an HTTP call does nothing or you forgot which class owns a `ref`.

---

## `quest:find`

Search references by a free-text keyword: class name, method name, `ref`, or PHPDoc comment.

```bash
php artisan quest:find forest
php artisan quest:find tree --with-comments
php artisan quest:find forest --full
```

| Argument / option | Role |
| --- | --- |
| `keyword` | Text to search. |
| `--with-comments` (`-c`) | Include PHPDoc in the listing. |
| `--full` (`-f`) | Namespace, file:line, and comments. |

Without `--full` / `--with-comments`, results are a table: No, Namespace, Class, Method, Reference.

---

## `quest:ref`

Single entry point that delegates to the commands above.

```bash
php artisan quest:ref --list
php artisan quest:ref --list --no-table
php artisan quest:ref --list --index=1,2,4
php artisan quest:ref --generate=16
php artisan quest:ref --g-uuid
php artisan quest:ref --track=forest.tree
```

| Option | Role |
| --- | --- |
| `--list` (`-l`) | Print every discovered reference. |
| `--no-table` (`-e`) | With `--list`: one block per item instead of a table. |
| `--index=` (`-i`) | With `--list`: keep only these 1-based row numbers, comma-separated. |
| `--generate=` (`-g`) | Call `quest:generate-ref` with this length. |
| `--g-uuid` (`-u`) | Call `quest:generate-ref --uuid`. |
| `--track=` (`-t`) | Call `quest:track-ref` with this value. |

With no option, the command prints an error (`No action : check your option`).

---

## `quest:publish`

Create `routes/quest.php` if it does not exist (same as `QuestRouter::createRouteFile()`).

```bash
php artisan quest:publish
```

Does not publish `config/quest.php`. For both files:

```bash
php artisan vendor:publish --tag=quest
```

---

## `php artisan about`

Laravel’s own command. Quest adds a **Quest** section (`version`, `channel`) via the service provider.

---

## PHPDoc and the tracker

`track-ref` and `find` surface the method’s docblock. Document the return shape so a `ref` is readable in the terminal:

```php
/** @return array{color: string, fruits: int} */
#[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
public function tree(string $color): array
{
    return ['color' => $color, 'fruits' => 18];
}
```

---

## Related

- [Quest](./quest.md)
- [Attributes](./attributs.md)
- [Guide](../README.md)
