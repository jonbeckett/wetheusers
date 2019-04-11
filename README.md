# WeTheUsers

### Prototype social blogging platform

WeTheUsers is a bare-bones social blogging platform written in PHP, using a MySQL database.


#### Features

* Posts use markdown syntax! (just like the Ghost blogging platform)
* You can post some posts friends only
* You can comment, and reply to comments!
* You can private message each other - and read the thread of messages
* You can chat in real time with one another
* You can add yourself to a directory of users to make yourself discoverable
* You can browse the firehose of everybody's content really easily
* The mobile interface is beautiful on phones and tablets
* You can choose to have as many, or as few events wired up to send email notifications as you want - and still not miss notifications even if you switch email notifications off - the site records them for you anyway!


#### Background

When I began building WeTheUsers, it was mostly out of frustration with the incumbent blog platforms. It evolved pretty quickly - going from nothing to what you see in the space of about a month.

I intentionally borrowed ideas from several other websites, and kept things deliberately simple;

* The use of markdown was a day-one decision, to avoid rich text editors, which never work correctly.
* The lack of templates was based on the observation that everybody at Tumblr lives in the dashboard; they never look at each other's public-facing blog posts.
* The ability for the site to work in mobile devices automatically was an early decision too - and really leaned on good HTML and CSS to achieve it.
* Asynchronous friending was a day-one decision too; where you could call somebody a friend, but they didn't have to call you a friend in return (Facebook, by contrast, is synchronous).
* The privacy controls were central to the experiment - letting people have the one thing that none of the blogging platforms allowed - the ability to restrict posts to only your friends.
* Private messaging was added mostly as a reaction to the woeful system in place at Tumblr - giving people the ability to see the conversation leading to a message, regardless of if they had "deleted" a message.
