<?php
// Stub — prevents the real classes/portaloutput.php (and its portalitem/portalcategory/
// portalcontent dependency cascade) from loading during tests. catalog.php requires this
// unconditionally at file scope even though portaloutput_class is only instantiated when
// the session user has a portal_id, which tests avoid by leaving portal_id unset/0.
