# MM-DS · sistema de diseño de Mi Monchis MX — v1.0.0

Fuente única de **tokens, CSS, componentes Twig, íconos, fuente y marca** para Public, Admin y Locals.
Aquí se edita; en las apps solo se **copia** (con `bin/mm-ds-sync`) y se **verifica** (con `bin/mm-ds-verify`).
Especificación: `Obsidian/17 Archivos de Claude/Diseño/02 MM-DS v1 sistema de diseno.md` · vitrina: `04 MM-DS v1 vitrina.html` (carga los CSS de esta carpeta).

Requisitos: solo PHP ≥ 8.1 en la Mac (sin Composer, sin npm, sin red). Esta carpeta es un repo git **local** (sin remoto).

## Qué contiene

```
MM-DS/
  VERSION                        versión semver del paquete
  manifest.json                  versión + huella SHA-256 de cada archivo + mapa de rutas + lista de íconos
  tokens/mm-tokens.css           variables --mm-* (color, tipografía, pesos 400/500/600/700, espacio, radios, sombras, z-index…)
  tokens/mm-tailwind.css         @theme inline para Tailwind 4 (solo Admin y Locals)
  css/mm-base.css                @font-face Outfit, reset ligero, foco global, .mm-sr-only/.mm-skip-link, estilos opt-in (.mm-body)
  css/mm-components.css          índice: importa css/components/*.css
  css/components/*.css           los 17 componentes + apoyo (icon, skeleton, peek, breadcrumb, popover, progress, logo)
  twig/components/Mm/*.html.twig componentes anónimos de Symfony UX TwigComponent (NO se instala el bundle aquí)
  icons/lucide/*.svg + LICENSE   51 íconos Lucide (ISC); la lista vive en manifest.json → «icons»
  fonts/                         Outfit variable 400–700, latino, woff2 + OFL.txt (licencia)
  brand/                         logo horizontal a color (oficial) · versión blanca e isotipo (PROVISIONALES)
  bin/                           mm-ds-manifest · mm-ds-verify · mm-ds-sync
```

### Dónde cae cada cosa en una app

| En el paquete | En la app |
|---|---|
| `tokens/`, `css/`, `fonts/`, `icons/`, `brand/` | `assets/mm-ds/<misma subruta>` |
| `tokens/mm-tailwind.css` | `assets/mm-ds/tokens/mm-tailwind.css` (**solo** Admin y Locals) |
| `fonts/` | Public: `assets/mm-ds/fonts/` · **Admin y Locals: `assets/fonts/`** (Tailwind junta `mm-base.css` en `assets/styles/app.css`, y el `url("../fonts/…")` del `@font-face` queda relativo a `assets/styles/`) |
| `twig/components/Mm/` | `templates/components/Mm/` |
| (huellas de lo copiado) | `mm-ds.lock` en la raíz de la app |

`assets/mm-ds/icons/` no choca con `assets/icons/` de `ux:icons:lock`. Esas carpetas son **de solo lectura en la app**: se editan aquí.

## Actualizar el paquete

1. Edita **solo** en `Sistemas/MM-DS/` (nunca la copia de una app).
2. Sube `VERSION` (patch: arreglo · minor: componente o token nuevo · major: cambio que rompe).
3. `bin/mm-ds-manifest` → regenera `manifest.json`.
4. `bin/mm-ds-verify` → debe terminar en `✓ MM-DS OK`.
5. `git add -A && git commit -m "MM-DS vX.Y.Z: …"` (en esta carpeta).
6. Si cambiaste algo visible, actualiza la vitrina y anótalo en el documento 02.

`bin/mm-ds-verify` (sin argumentos) comprueba, además de las huellas: sin colores literales fuera de `tokens/` (el `grep` «hex fuera de mm-tokens.css»), sin `font-weight` 800/900, sin clases de color por defecto de Tailwind en los `.twig`, todo `var(--mm-*)` definido, `mm-components.css` al día, íconos = lista del manifiesto, fuente y licencia presentes, marca «provisional» marcada y que la vitrina cargue estos CSS.

## Sincronizar a una app

```bash
bin/mm-ds-sync admin          # también: locals · public · o la ruta de la carpeta
bin/mm-ds-sync admin --dry-run   # solo muestra qué copiaría
bin/mm-ds-sync admin=/ruta/al/worktree   # carpeta con otro nombre (p. ej. .worktrees/mm-ds-a1); verify acepta lo mismo
```

Copia los archivos a las rutas de la tabla, retira los que el paquete ya no trae y escribe `mm-ds.lock` (`{package, version, files:{ruta: sha256}}`).
Luego, en la app: commit «MM-DS vX.Y.Z» (solo los archivos copiados + el lock).

## Verificar antes de armar un zip de despliegue

```bash
bin/mm-ds-verify public admin locals     # o una sola
```

- `✓ … sin deriva` → se puede armar el zip.
- `mm-ds.lock` **vacío** = «sin sincronizar»: **no es error** (estado inicial hasta A1/L1/P1).
- Falla (exit 1) si: un archivo copiado se editó a mano (**deriva**), falta, hay un archivo ajeno dentro de `assets/mm-ds/` o `templates/components/Mm/`, o la app quedó en una versión anterior del paquete (`--allow-outdated` lo tolera solo como aviso).
- **Si falla, no se despliega:** vuelve a sincronizar (o, si el cambio era bueno, súbelo al paquete y sincroniza).

La lista de pre-zip de cada app añade (ver doc 02, B5): «hex fuera de `mm-tokens.css`» y «clases de color de Tailwind por defecto».

## Cómo se carga en una app (lo hacen P1/A1/L1, no este paquete)

- **Public (AssetMapper):** en `assets/styles/app.css`: `@import url("../mm-ds/tokens/mm-tokens.css"); @import url("../mm-ds/css/mm-base.css"); @import url("../mm-ds/css/mm-components.css");` y `class="mm-body"` en `<body>`. **Con `url(...)`**: el compilador de AssetMapper (Symfony 7.2) solo reescribe `url()`; un `@import "ruta"` pelón se queda sin resumen y da 404 en el navegador (P1 lo comprobó). En `event.css` (minisitio) solo tokens + base.
- **Admin / Locals (Tailwind 4):** `@import "tailwindcss";` luego los tres anteriores y `@import "../mm-ds/tokens/mm-tailwind.css";` (el `@theme inline` apunta a `var(--mm-*)`, así los tokens siguen siendo la única fuente).
- **Twig:** requiere `symfony/ux-twig-component` (+ `symfony/ux-icons` en Admin/Locals; `ux:icons:lock` guarda los SVG en `assets/icons/`). Uso: `<twig:Mm:Button variant="primary" icon="plus">Nuevo</twig:Mm:Button>`.
- **Fuente:** `mm-base.css` ya trae el `@font-face` (sin Google Fonts).

## Qué NO hace v1.0.0 (pendiente)

- No instala nada en las apps ni toca sus plantillas o CSS; solo existe un `mm-ds.lock` vacío por app.
- **Shell de panel** (sidebar, topbar, `Mm:Shell`) y sus estilos: siguen en la vitrina hasta A3/L2.
- **JS (Stimulus)** de `mm-sheet`, tabs, alertas y toasts: P5/A2. Los `.twig` ya emiten los `data-*` previstos.
- Alias de migración de tokens viejos (`--mm-weight-*`, `--evt-*`, `--social-*`…): son por app y los crean P1/A1/L1.
- Logo blanco e isotipo son derivados **provisionales**; hay que sustituirlos por los oficiales cuando lleguen.
- Los `.twig` pasan el análisis de sintaxis de Twig 3.24, pero no se han renderizado con el bundle (no está instalado); confirmar `attributes.defaults()` y los bloques contra la versión instalada.
