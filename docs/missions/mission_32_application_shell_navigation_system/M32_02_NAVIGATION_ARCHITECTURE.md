# Navigation Architecture

## Structure
```
moghare360-ui-shell.php
  moghare360_get_shell_menu(roleMode)
  moghare360_render_shell_start(pageTitle, activeModule, roleMode)
  moghare360_render_shell_end()

erp-app-shell-demo.php
  → render_shell_start
  → dashboard content
  → render_shell_end
```

## Asset Stack
1. M31 design tokens, rtl, layout, components
2. M32 moghare360-shell.css
3. M32 moghare360-shell.js (deferred)

## Layout Grid
```
sidebar | topbar
sidebar | content
```

## Mobile
Fixed sidebar overlay + backdrop; toggle via topbar button

## Final Architecture Decision
PHP render helper + static CSS/JS; no server-side navigation state beyond demo role querystring.
