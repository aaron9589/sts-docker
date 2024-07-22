
# Introduction

Dockerizing POC for the Shipper-Driven Trafffic Simulator app.

## Getting Started

1) Ensure you have docker installed on your device of choice. For Mac, if you're using Docker Desktop - ensure the app is open (Look in Applications) then fire up terminal. I suggest using something like [brew.sh](brew.sh) to make it easy to install docker: `brew install docker`
3) Clone this repo: `git clone https://github.com/aaron9589/sts-docker`
4) `cd` into the newly created folder
5) Edit the `docker-compose.yml` file where the comments are located to suit your use case. Read the comments alongside each setting!
6) Run `docker compose up -d` and wait for the containers to start
7) browse to http://localhost:8980/sts/ (or whatever port you mapped to) and start using STS!
