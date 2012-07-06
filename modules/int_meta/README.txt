Document Revision Information:
$Id: README.txt,v 1.1.2.6 2009/01/24 21:52:21 fractile81 Exp $


Integrated MetatagsIntegrated Metatags
By: Craig A. Hancock
Copyright 2008-2009 by Craig A. Hancock


-----------------------------------------------------------------------------
NEW DOCUMENTATION IS COMING! I'm currently working on new documentation, that
will also be available in an online format.  See my ticket at
http://drupal.org/node/230753 for the current status.  Once completed, this
document will be replaced with a text version of the new documentation.
-----------------------------------------------------------------------------


This README covers both the Drupal 5 and Drupal 6 branches of the Integrated
Metatags project.  If you need to contact the maintainer, please visit
http://drupal.org/project/int_meta .  Any additional releases or documentation
for this module will be available there as well.

See the LICENSE.txt and COPYRIGHT.txt files for more information on this
module's licensing and copyright information, respectively.


===============================
Table of Contents
===============================
S01. Description
S02. Installation/Configuration
S03. How It Works
S04. Extending This Module
===============================


===============================
S01. Description
===============================
Integrated Metatags allows you to expose node data in META-tags on full-page
node views.  Node, taxonomy, and user information are all available out of the
box, with the option to name the selected data however needed.  Any data can be
combined into a comma-separated list as a single Metatag as well.  Individual
settings are stored for each content type, plus default settings that can be
inherited by any other content type.  This module also comes with a companion
module that allows the integration of CCK field data, greatly expanding the
possibilities of exposable data.

NOTE that only the "keywords" and "description" Metatags will have any benefit
with today's search engines.  This module can be used to dynamically generate
these Metatags.  Otherwise, other Metatags will NOT improve your site's Search
Engine Optimization (or SEO) with most search engines.  If you control your own
crawler, this extra data could be used for things like a faceted search.

Key Features:

  - Expose data from the node object - Select which data fields in the node
    object should be used as a Metatag. Support for taxonomy terms and user
    information are also available. Each exposable data field can then be named
    however the user desires.
  - Configurable on a per-content type basis - Metatag settings are set for
    each content type. A default configuration can also be used to allow indi-
    vidual content types to inherit a base set of Metatags. Predefined, static
    Metatags can also be added.
  - Extendible - Displayed Metatags can be extended in two ways:
      1. Adding Metatag data directly to the node object will allow it to auto-
	     matically be displayed along with any other default Metatags.
      2. A hook can be used to expose different kinds of data sources that are
	     associated with your node, allowing custom-built data to be easily
		 exposed as well.
  - CCK Support - By enabling the included CCK module in this project, you can
    expose your CCK fields and data as Metatags, too.
  - Combine like-named fields into one Metatag - Multiple fields and data can
    be combined into one comma-separated list Metatag.  Works well for creating
    a dynamic "keywords" Metatag based on associated taxonomy terms, for
    example.
  - Basic support for the Token module - When defining static Metatags, you can
    enable the Token module (http://drupal.org/project/token) and utilize the
    power of that module as well!
  - Show Metatags when nodes are displayed as a teaser (Drupal 6 ONLY) - You
    can optionally have Metatags displayed whenever nodes are rendered as a
    teaser.  Otherwise, both Drupal 5 and 6 versions will only show Metatags
    on a full-page viewing of a node.


===============================
S02. Installation/Configuration
===============================
NOTE: Out of the box, this module supports both Drupal 5 and 6.  This will not
be ported any further back, but future versions of Drupal should be.

WARNING: This module has not been tested with PostgreSQL.  If you are willing
to do this testing (specifically in Drupal 5), please create a task in the
issue queue for this module.

Installation of this module is as simple as placing it in your modules direc-
tory and enabling it under Module Administration.  This will create the single
table int_meta_fields.  This table will store all of the defined Metatags.
When this module is uninstalled, the same table will be removed, along with all
data in it.

You can install/enable the Integrated Metatags - CCK (int_meta_cck) module to
expose fields associated with CCK content types.  These fields will auto-
matically show up in the dropdown listings on the configuration settings pages.

Under adminstration settings, there is an option to change the "Integrated
Metatags Settings".  From this page, you can configure how Metatags are
trimmed, with specific options for the "description" and "keywords" tags.  Each
has the option to select to trim based on word count or string length.  Select
the desired radio button and enter a value to cap at.  To not trim at all,
enter 0 as the value for the trimming cap.  These settings are applied to
every Metatag this module will produce.

NOTE: various websites will say different things about the maximum length
of a Metatag, especially the description and keywords tags.  For example,
Google will only display the first 150 characters of the description when
displaying search results, but claims to retain more.  It has also been
observed that search engines tended to dislike sites that have too many
keywords in their Metatags.

You configure each content type individually from their content type page.  A
"Metatags" tab/local task will appear for each content type.  The URL for this
will be something like admin/content/types/{type}/metatags (Drupal 5) or
admin/content/node-type/{type}/metatags (Drupal 6).  To configure the default
settings for all types, go to Administer > Content management > Integrated
Metatags (or admin/content/int_meta).

If you go to one of these pages, you will see various configuration settings.
The options on this form are the same for both the default Metatags settings
form and any content-type specific settings form.  The default settings form
will not have an option to inherit Metatags.

Here is a list of the configuration options:

 - Enable:        You can selectively enable Metatags for any content type.
                  The default settings can also be enabled/disabled, but
                  if disabled any inheriting content types will not include
                  the default Metatags.
 - Show empty?:   Check this box if you want to display Metatags that have
                  no values.
 - Inherit?:      (Content-types only) Whether to inherit the default
                  Metatags that are defined.  Note that the default settings
                  form must also be enabled.
 - Teasers?:      DRUPAL 6 ONLY!  Check this field to have your Metatags
                  displayed when a node is rendered as a teaser as well.
                  Otherwise, Metatags will only be shown on the full-page
                  viewing of a node.
 - Manual/Static: You can specify metatags that you always want displayed by
                  entering one tag per line in the form of "<name>|<value>".
                  If you have the Token module installed, you can use the
                  available node tokens in these fields.  You can only enter
                  one static Metatag per line.

You can create dynamic Metatags using the form in the "Dynamic Metatags" field-
set.  Here you can:

 - Enable:  You can selectivly enable or disable any of the dynamic Metatags.
            Newly created Metatags are enabled by default.
 - Combine: If checked, this Metatags will combine with any other Metatag that
            has the same name.  Combined Metatags will create a comma-separated
            list of unique values.
 - Delete:  On next save, any Metatag with this checkbox checked will be
            deleted.
 - Field:   When creating a new dynamic Metatag, you must select what field to
            pull the Metatag's value from.  Once set, this cannot be changed.
 - Name:    Give the Metatag a name.  You cannot create a Metatag that has both
            the same name and field.

REMEBER: The field name of a Metatag will be used by default if a name is not
speficied.

NOTE: If a content type has not yet been configured, it will inherit the
default configuration.


===============================
S03. How It Works
===============================
A Metatag is stored as an object with the following data:

  - field:	 The field this Metatag represents.
  - type:    What content type this Metatag relates to.  Blank for default.
  - name:    How to name this field.  Blank to use the field name.
  - status:	 Set to 0 if disabled, or 1 if enabled.
  - combine: Whether to combine similarly-named Metatags into a comma-
             separated list.
  - values:	 An array of values that are associated with this field.

When hook_nodeapi(); invokes the "view" operation, this module will check and
make sure the node is on a full-page view, or in Drupal 6 it will also check
if Metatags have been enabled for node teasers and whether the node is being
rendered as a teaser.  If so, it will load all of the fields for the content
type and execute the "fields" operation for all hook_int_meta(); implemen-
tations (See S04 for more information on this hook).  These will be merged
with any data already in the $node->int_meta array.  Once all of this has been
loaded, all implementations of hook_int_meta(); are called again, this time
with the "load" operation, meant to populate all of the Metatag values.

Once the "alter" operation for hook_nodeapi(); is called, your version of
Drupal will dictate what happens next:

 - Drupal 5.x: The Metatags will run through a function to combine any like-
               named fields into a single field which are configured to do so.
               After this, the Metatag rendering will take place, calling
               drupal_set_header(); to create each of the Metatags.  With
               combined fields, a comma-separated list of UNIQUE values will be
               generated before rendering the Metatag.
 - Drupal 6.x: This will function much like the Drupal 5.x process, but
               instead the processing of the Metatags is off-loaded to a
               storage function.  When the page is being themed, the storage
               function is called to fetch any stored data, which is then
               set to render within the <head>-tag of the page.

When using static Metatags, any other like-named static tag will automaticly
combine with it.  So, if your base settings defined "foo|bar" and a content
type inherited Metatags and defined something like "foo|baz", the Metatag
will have the value of "baz, bar" when rendered in the page.  You can combine
these with dynamic tags by giving them all the same name, and then selecting
the "combine" setting for the dynamic tag.


===============================
S04. Extending This Module
===============================
This module was built to allow as much exposure of the generated Metatags as
possible.  This is done through a combination of the node object and a hook.

Integrated Metatags will use whatever data is in the $node->int_meta array
when it comes time to render Metatags.  So long as the data is formatted as
expected, any additional Metatags can be added at any time prior to the 'alter'
operation on hook_nodeapi();.

To add fields to the dropdown on the configuration pages, a module must imple-
ment a hook_int_meta(); function.  This function takes two arguments: the
operation, and a pass-by-reference argument that changes depending on the
operation.  The following operations must be supported:

  - fields: Returns an array of fields that should be supported.  The second
            argument to this operation is the content type the fields are
		    being generated for.
  - load: The second argument is a node object.  This operation should
          iterate over the $node->int_meta object and populate the
		  'values' property for each Metatag in the array that the hook
		  supports.  The 'values' property should be an array.

An example of this hook can be found in the int_meta_cck.module file.  The
int_meta.module file also implements this hook.
