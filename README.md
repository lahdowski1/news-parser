# This is an application that parses news

# Run a composer install

# php bin/console doctrine:database:create to create your database

# Steps
## (1) Clone the repo
## (2) Run a composer install (Run composer install –ignore-platform-reqs if you get any
error)
## (3) Add the following to your .env file:
MESSENGER_TRANSPORT_DSN=amqp://kvwujjsc:nT0_psB7iUO6g1T5X7v53DXBnzOz252@rattlesnake.rmq.cloudamqp.com/kvwujjsc/NewsQueue
## (4) Configure your database configuration in your .env as shown below:
## DATABASE_URL=mysql://news-parser:1234567@127.0.0.1:8889/newsparser?serverVersion=5.7
## (5) Run the following to create the database
 php bin/console doctrine:database:create
## (6) Run the following to create the database tables via migration:
 php bin/console doctrine:migrations:migrate
## (7) Run the following command in a new terminal/cron to consume the news from RabbitMQ and store in the database:
php bin/console messenger:consume -v
## (8) Run the following command in a separate terminal/cron to publish the news to RabbitMQ
 php bin/console GenerateNewsCommand
## (9) Run php bin/console server:run in a separate terminal in this project to run this application
## (10) Visit http://127.0.0.1:8000/users/ to view all Users and create a new User
## (11) Visit http://127.0.0.1:8000/users/new to create a new User
## (12) Visit http://127.0.0.1:8000/news to view the news. Only an admin can delete a news, a moderator can only view the news.


