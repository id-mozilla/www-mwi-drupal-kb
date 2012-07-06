// $Id: README.txt,v 1.3 2009/06/18 13:38:15 mfer Exp $

###   ABOUT   #############################################################################
Shared Edit, Version 1.0

Authors:

 Matt Farina, aka, mfer
   matt.farina@gmail.com
   http://www.mattfarina.com

Requirements: Drupal 6.x

###   FEATURES   ##########################################################################

- Enables you to individually choose what users can edit a node.

###   INSTALLATION   ######################################################################

1. Download and unzip the Shared Edit module into your modules directory.

3. Goto Administer > Site Building > Modules and enable Share Edit.

4. Goto Administer > Users > Permission and enable a role to use the Shared Edit permission.

5. Go to Administer > Content > Types and enabled shared access on the node types you want it on.

6. On nodes your can edit add others as editors.

###   API   ###############################################################################

The CRUD API for shared access is documented in shared_edit.api.inc.

###   CHANGELOG   #########################################################################

Shared Edit 1.1, 2009-6-18
----------------------
- When a node is deleted removing data stored by shared edit.
- Moved the page callbacks into shared_edit.pages.inc.
- Updated to use a CRUD api detailed in shared_edit.api.inc
- Added more and better error and watchdog messages.

Shared Edit 1.0, 2009-6-17
----------------------
- Initial release