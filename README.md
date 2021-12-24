## Movie Information Service

This service allows anyone to search for a Movie or TV Series and receive relevant information such as the plot, actors, directors, runtime, IMDB ratings and more. The goal is to entice customers to register for an account to unlock additional customized features, such as: 
- The ability to manage a friends list 
- Like or dislike movies and TV shows 
- Schedule a watch party with friends through google calendar 
- Build out a profile page
- Receive new movie and TV series recommendations based on the account's recent searches or interactions. 

This project is divided into 3 parts: Front-End, Back-End and DMZ API
1. The customer interacts with the Front-End and generates requests such as movie queries or login authentications. All requests are sent through a RabbitMQ messaging broker to prevent direct access between the Front-end and Back-End MySQL Database.
2. The Back-End MySQL Database consumes RabbitMQ messages and performs SQL queries to process requests. 
3. If a customer queries for a movie not found in the MySQL Database, the DMZ API Server makes an API call to obtain the latest information about the movie.  

For example: A customer requests information about the movie "Cars 2". The Front-End produces a RabbitMQ message that is consumed by the Back-End. The Back-End MySQL Database receives the request to lookup Cars 2. It first checks if Cars 2 is in its local Movie table. If Cars 2 is found, the movie information is returned to the Front-End via RabbitMQ. If Cars 2 is not in the MySQL database, the Back-End produces an API RabbitMQ message that is consumed by the DMZ API Server. The DMZ API Server makes an OMDB API call to obtain information about Cars 2 and returns the information to the Back-End. The Back-End MySQL Database creates a new movie and inserts the API information into the Database. If another customer searches for Cars 2, the Database will return its local movie information and not make another API call.

## Environment Structure

1. Production Environment
   - Front End
     - Standby Failover
    - Back End
      - Standby Failover
    - DMZ
      - Standby Failover
2. Quality Assurance Environment
   - Front End
   - Back End
   - DMZ
3. Development Environment
   - Front End
   - Back End
   - DMZ
4. Deployment Server

## Prerequisites

The following packages need to be installed.

All Environment Machines require: ```sudo apt install php php-amqp vim curl openssh-server```

Front end: ```sudo apt install apache2```

Back end: ```sudo apt install rabbitmq-server software-properties-common apt-transport-https mysql-server mysql-client php-mysqli```

DMZ API: ```sudo apt install php-curl```

## Setup

This section contains steps about any additional configuration that needs to be done with some packages.

### ZeroTier VPN

Any VPN to network the virtual machines together should be fine. We used ZeroTier. To install ZeroTier and connect to a network:
```
curl -s https://install.zerotier.com/ | sudo bash
sudo zerotier-cli join <Network ID>
```
### Apache Web Server

The web page files are deployed into /var/www/html: 
```
sudo cp <Front-End branch> /var/www/html
```

### RabbitMQ

Enable the management plugin. The creation of exchanges, queues, bindings and policies is automated via testRabbitSetup.sh.
```sudo rabbitmq-plugins enable rabbitmq_management```

Rabbitmqadmin requires the Erlang programming package. Erlang is not in the apt package manager by default. Add Erlang to the apt package manager and install: 
```
wget -O- https://packages.erlang-solutions.com/ubuntu/erlang_solutions.asc | sudo apt-key add -
echo "deb https://packages.erlang-solutions.com/ubuntu focal contrib" | sudo tee /etc/apt/sources.list.d/rabbitmq.list
sudo apt install erlang
```

### MySQL Server

Set up the default super user: ```mysql> CREATE USER 'testuser'@'localhost' IDENTIFIED BY '12345';``` ```mysql> GRANT ALL PRIVILEGES ON * . * TO 'testuser'@'localhost'; mysql> FLUSH PRIVILEGES;```

After deployment, run the following SQL file in the CLI to create the necessary database tables: ```sql> source <file name>```
```
createFriendsTable.sql
createMovieTable.sql
CreateRatingTable.sql
CreateUserFriends.sql
createUserTable.sql
createWatchTable.sql
1-database.sql
friend_request.sql
friends.sql
reactiondatabase.sql
recommendationTable.sql
user.sql
```
### Systemd

The RabbitMQ consumer file, ```testRabbitMQServer.php```, has been converted to a service and is managed by systemd. This file is enabled at boot and acts as a listener for incomming messages. The configuration file ```keepRunning.service``` tells systemd to manage ```testRabbitMQServer.php```. Place this file in the systemd directory:
```
sudo cp keepRunning.service /etc/systemd/system
sudo systemctl daemon-reload
sudo systemctl start keepRunning.service
sudo systemctl enable keepRunning.service
```
testRabbitMQServer.php can now be managed using systemctl commands:
```
sudo systemctl status keepRunning.service
sudo systemctl start keepRunning.service
sudo systemctl stop keepRunning.service
```
