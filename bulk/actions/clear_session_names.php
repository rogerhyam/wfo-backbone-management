<?php

unset($_SESSION['created_names']);
header("Location: index.php?action=view&phase=created");