<?php
/** Create or update an item of any collection (hidden field "collection"). Admin session · POST · CSRF · validation. */
declare(strict_types=1);
require __DIR__ . '/_collection.php';
$key = post_str('collection', 20);
if (!isset(collection_schemas()[$key])) { require_admin(); respond(false, 'Unknown collection.', [], null, 400); }
collection_save($key);
