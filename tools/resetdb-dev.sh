echo "Resetting dev datbase"
php app/console doctrine:database:drop -e dev --force
php app/console doctrine:database:create -e dev
php app/console doctrine:migrations:migrate -e dev --no-interaction