<?php
unlink('../bulk/csv/' . $_GET['file_name']);
header('Location: index.php?action=view&phase=csv');