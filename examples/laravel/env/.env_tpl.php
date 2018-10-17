<?php
echo "
DB_HOST={$apollo['mysql.url']}
DB_PORT={$apollo['mysql.port']}
DB_DATABASE={$apollo['mysql.db']}
DB_USERNAME={$apollo['mysql.user']}
DB_PASSWORD={$apollo['mysql.password']}

REDIS_HOST={$apollo['redis.url']}
REDIS_PORT={$apollo['redis.port']}
REDIS_PASSWORD={$apollo['redis.password']}
REDIS_DB={$apollo['redis.db']}
";
