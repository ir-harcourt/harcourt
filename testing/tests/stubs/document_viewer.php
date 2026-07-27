<?php
// Stub — prevents the real document_viewer.php from loading during tests.
// catalog.php require_once's this whenever the session user lacks catalog access
// (or needs to view a landing/login document); tests only need that branch to
// complete without side effects.
