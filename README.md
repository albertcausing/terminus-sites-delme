# Terminus Plugin Removing Yourself As Team Member From Other Sites.

At some point, you'll get a massive list of sites on your dashboard 
because you forget to remove yourself to the team members of the client site. 

###How to install:
```
$ mkdir -p ~/terminus/plugins
$ cd ~/terminus/plugins
$ git clone https://github.com/albertcausing/terminus-sites-delme.git
$ terminus auth login
$ terminus sites delme
```

###Commandline equivalent but need to change UUID and Email:
```
terminus sites list --team | awk -F'|' '{print $2 "|" $6}' | grep -v '335d2c2a-bb89-4185-98f4-257d418d5bfe' | awk -F'|' '{print $1}' | sed 's/ //g' | grep -Ev "Name|^$" | xargs -n 1 -I SITE terminus site team remove-member --site=SITE --member=albert@getpantheon.com
```

Thank you.
