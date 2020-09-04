Requirement installation:
sudo apt-get install php

Requirement validation:
php -version

Run:
php \<path to the project folder\>/texas-hold-em.php < \<path to your test file\> > \<path to your results file\>

Notes:

Input validation added for:
- Duplicate cards.
- String discrepancies like the incorrect number of characters for Board or Hands or wrong characters within them.
- There is a notification if no Hands are added to the input string.
- Wrong upper / lower case characters are corrected  for calculations, left in the returned string since there was a requirement that no changes should be done to the string itself. That has an impact when ordering identical hands alphabetically.

Example:

Uppercase used for d within AD4s:

Input string: 4cKs4h8s7s AD4s Ac4d As9s KhKd 5d6d

Output string: AD4s=Ac4d 5d6d As9s KhKd


The same with lowercase d:

Input string: 4cKs4h8s7s Ad4s Ac4d As9s KhKd 5d6d

Output string: Ac4d=Ad4s 5d6d As9s KhKd 


Other types of validation can be added as needed.


To the best of my knowledge there are no limitations to this script.

I do recognize my inexperience with this game and it's edge cases.

Would be grateful for any constructive criticism.
