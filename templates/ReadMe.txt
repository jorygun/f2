email strategy

each email has two parts: a reciepiet part and optional admin part
each email is in a file designated by an action code.
email is called wtih msg(action,recipeint,data_array);

data array is assoc array.  text in message replaced by
   ::key:: goes to array[key]
   
First line of message is also subject

emails terminate at EOF or #########
Anything after ####### is the admin email.
