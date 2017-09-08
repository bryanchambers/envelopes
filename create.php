<?php

require 'db.php';

echo 'Test';

$dbc = dbConnect();

var_dump($dbc);

dbCreateTable($dbc, 'envelopes', tableDefs('envelopes'));