# CFBPicks: A Live College Football Pick'em League
A custom pick'em platform with unique scoring and automated user notifications.

## About
CFBPicks is a full-stack web application built for college football fans seeking a more engaging and feature-rich alternative to mainstream fantasy football platforms. My first large-scale side project, I aimed to create a fun and competitive environment for users to pick weekly winners and compete in a season-long league.

The site is engineered with a Bootstrap / dynamic JavaScript interface and a secure PHP backend, utilizing the PhpMyAdmin database to meticulously manage all user accounts, weekly game schedules, and user-submitted picks. Player scores are updated frequently via a CPanel Cron Job. Web hosting is managed by GoDaddy.

## Key Features
#### Live Scoring
Integrates a live scoring service that automatically fetches and displays game information from Yahoo Sports on dynamic cards, updating within five minutes of the action.

#### Comprehensive Admin Panel
Allows for easy management of weekly games, complete with custom point values, unique underdog bonuses, and a weekly conference champion "futures" pick.

#### Automated Notifications
A server-side cron job automatically sends SMS notifications to users who have forgotten to submit their picks for an upcoming game, ensuring maximum participation.

#### Interactive User Dashboard
Provides a clean, modern interface for players to make their picks, view picks from others in their league, and track their real-time standings on the leaderboard. The home page shows quick-glance statistics such as the user's rank, score, and the weekly bonus prize.

## Tech Stack
This project is built with a combination of robust and modern web technologies:

#### Backend: PHP

#### Database: MySQL

#### Frontend: JavaScript, HTML5, CSS3

#### Frameworks/Libraries: Bootstrap, ECharts.js

#### External: Google Apps Script, Yahoo Sports

## Contact
Caden McCann - cadenmccann3@gmail.com, cpmccann@crimson.ua.edu
