# Telegram Clean Truth Game
A clean truth game for telegram groups, Built using Php and telegram API

Features:
* Can be used in any group.
* Supports any amount of players (you can play with a friend or with the group).
* Control the bot directly from the chat (with commands).

<br>Requirements:
* Hosting (any type).
* MySql database.
* Telegram bot (can be created using BotFather).

<br>How to:
* Upload all files and folders to the host.
* Create mysql database and add the code from (mysql.sql) file.
* Setup database connection in (/inc/db.php).
* Create bot using BotFather
* Put bot token in (/API.php) - ($bot_token = 'bot:token';)
* Set bot WebHook using the following link:<br>
<code>https://api.telegram.org/bot<bot_token_here>/setWebhook?url=https://mysite.com/truth_game/API.php</code><br>
Replace <code><bot_token_here></code> with your bot token<br>
Replace <code>https://mysite.com/truth_game/API.php</code> with the link to your API.php<br>
You will get a confirmation message (<code>{"ok":true,"result":true,"description":"Webhook was set"}</code>)
* Go to the bot chat and send /truth_help
  
![image](https://user-images.githubusercontent.com/25286081/146096642-d44e9657-f914-45e7-b675-3bf0bf907ce0.png)
  
  <br>
<p>You can add the bot to any group and send /truth_help.</p>
<p><strong>Please notice:</strong> you will have to manually add the questions to (questions table).</p>
<p><strong>Please notice:</strong> that if you want to add the bot to multiple groups make sure to create another API (API.php) and another database.</p>  
