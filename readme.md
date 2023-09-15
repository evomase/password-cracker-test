# PASSWORD CRACKER

### How to install

You would need to have Docker and Docker Compose installed on the machine. Run the following command below:

```bash
docker compose up -d
docker compose exec php composer install
```

### How to run the password cracker

Run the following command:

```bash
docker compose exec php php crack
```

Word dictionary downloaded from [https://github.com/dwyl/english-words](https://github.com/dwyl/english-words)

:-)