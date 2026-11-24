<?php

// Test memakai database yang sama. Setiap test dibungkus transaction
// dan di-rollback di akhir, jadi data aplikasi tidak ikut kotor.
return require __DIR__ . '/db.php';
