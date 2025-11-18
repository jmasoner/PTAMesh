@echo off
REM ============================================================
REM PTAMeshScaffold.bat
REM Creates the PTAMesh repository folder structure and empty files
REM so you can open in VS Code and paste in code.
REM ============================================================

set ROOT=PTAMesh

echo Creating PTAMesh repository structure...

REM Root folders
mkdir %ROOT%
mkdir %ROOT%\wordpress
mkdir %ROOT%\wordpress\includes
mkdir %ROOT%\wordpress\includes\traits
mkdir %ROOT%\wordpress\templates
mkdir %ROOT%\wordpress\assets
mkdir %ROOT%\extension

REM Root files
echo # PTAMesh — ProTraxer Amazon Mesh > %ROOT%\README.md
echo Bootstrap tasks and notes will go here. > %ROOT%\Tasks.md

REM WordPress plugin files
type nul > %ROOT%\wordpress\ptamesh.php
type nul > %ROOT%\wordpress\includes\class-ptamesh-products.php
type nul > %ROOT%\wordpress\includes\class-ptamesh-rest.php
type nul > %ROOT%\wordpress\includes\class-ptamesh-receiving.php
type nul > %ROOT%\wordpress\includes\class-ptamesh-jobcart.php
type nul > %ROOT%\wordpress\includes\class-ptamesh-pricing.php
type nul > %ROOT%\wordpress\includes\class-ptamesh-normalize.php
type nul > %ROOT%\wordpress\includes\class-ptamesh-admin.php
type nul > %ROOT%\wordpress\includes\traits\trait-ptamesh-logger.php
type nul > %ROOT%\wordpress\includes\traits\trait-ptamesh-security.php
type nul > %ROOT%\wordpress\templates\admin-receiving.php
type nul > %ROOT%\wordpress\assets\admin.css
type nul > %ROOT%\wordpress\assets\admin.js

REM Extension files
type nul > %ROOT%\extension\manifest.json
type nul > %ROOT%\extension\content.js
type nul > %ROOT%\extension\background.js
type nul > %ROOT%\extension\ui.css

echo Done. Repository scaffold created under %ROOT%.
echo Open the PTAMesh folder in VS Code and paste in the code.
pause
