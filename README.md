# Terminus Plugin Removing Yourself As Team Member From Others Site.

At some point, you'll get a massive list of sites on your dashboard 
because you forget to remove yourself as team members from client site. 

###How it works:
1. Get all sites associated to your account
2. Filter #1 by membership equals "Team"
3. Filter #2 by ownership not equals to you
4. Deassociate membership from resulting sites list

Note: 
- Sites from organization will be skipped because of Filter #1
- --cached option will use cached sites list

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
terminus sites list --team | awk -F'|' '{print $2 "|" $6}' | \
 grep -v '335d2c2a-bb89-4185-98f4-257d418d5bfe' | \
 awk -F'|' '{print $1}' | sed 's/ //g' | grep -Ev "Name|^$" | \
 xargs -n 1 -I SITE terminus site team remove-member --site=SITE --member=albert@getpantheon.com
```

Thank you.
