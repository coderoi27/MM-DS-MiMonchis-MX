# MM-DS · sistema de diseño de Mi Monchis MX — v1.1.0

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
  css/mm-components.css          índice: importa css/components/*.css (forma url(), para AssetMapper)
  css/mm-components.tailwind.css el mismo índice con @import "…" (Tailwind 4 lo incrusta; solo Admin y Locals)
  css/components/*.css           los 17 componentes + apoyo + panel v1.1.0 (shell, stat-card, form-section, chart)
  twig/components/Mm/*.html.twig componentes anónimos de Symfony UX TwigComponent (NO se instala el bundle aquí)
  icons/lucide/*.svg + LICENSE   81 íconos Lucide (ISC); la lista vive en manifest.json → «icons»
  fonts/                         Outfit variable 400–700, latino, woff2 + OFL.txt (licencia)
  brand/                         logo horizontal a color (oficial) · versión blanca e isotipo (PROVISIONALES)
  bin/                           mm-ds-manifest · mm-ds-verify · mm-ds-sync
```

### Dónde cae cada cosa en una app

| En el paquete | En la app |
|---|---|
| `tokens/`, `css/`, `fonts/`, `icons/`, `brand/` | `assets/mm-ds/<misma subruta>` |
| `tokens/mm-tailwind.css` | `assets/mm-ds/tokens/mm-tailwind.css` (**solo** Admin y Locals) |
| `fonts/` | Public: `assets/mm-ds/fonts/` · **Admin: `assets/fonts/`** (Locals, con `linkedCss`: `assets/mm-ds/fonts/`) (Tailwind junta `mm-base.css` en `assets/styles/app.css`, y el `url("../fonts/…")` del `@font-face` queda relativo a `assets/styles/`) |
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
- **Admin / Locals (Tailwind 4):** `@import "tailwindcss";` luego tokens y base, **`@import "../mm-ds/css/mm-components.tailwind.css" layer(components);`** (no `mm-components.css`: Tailwind no incrusta los `@import url()` anidados y las rutas quedarían relativas a `assets/styles/`) y `@import "../mm-ds/tokens/mm-tailwind.css";` (el `@theme inline` apunta a `var(--mm-*)`, así los tokens siguen siendo la única fuente).
- **Twig:** requiere `symfony/ux-twig-component` (+ `symfony/ux-icons` en Admin/Locals; `ux:icons:lock` guarda los SVG en `assets/icons/`). Uso: `<twig:Mm:Button variant="primary" icon="plus">Nuevo</twig:Mm:Button>`.
- **Locals (`linkedCss` en `manifest.json`):** tokens, base y componentes se cargan por `<link>` desde `assets/styles/mm-ds.css` (con `@import url(...)`, capas `base`/`components`) y solo `mm-tailwind.css` entra al build de Tailwind. Así login, registro y landing se ven bien aunque falle el build de Tailwind o el JS; la fuente queda en `assets/mm-ds/fonts/` (el Tailwind CLI no reescribe los `url()` de los archivos importados).
- **Fuente:** `mm-base.css` ya trae el `@font-face` (sin Google Fonts).


## v1.1.0 — panel (Admin A2/A3, 20-nov)

- **Tokens de panel:** `--mm-content-max` (80rem) y `--mm-content-max-form` (64rem), anchos del shell (`--mm-shell-sidebar` 264px, `--mm-shell-rail` 72px, `--mm-shell-topbar` 56px), `--mm-control-panel` (36px) y `--mm-control-panel-touch` (44px), `--mm-row-panel`, colores de sidebar oscura (`--mm-sidebar-*`) y de gráfica (`--mm-chart-*`).
- **Densidad de panel:** `data-shell` en `<body>` pone cuerpo de 14 px y controles de 36 px (44 px con `pointer: coarse`); tablas de 40/36 px; encabezado de 24 px.
- **Componentes nuevos:** `Mm:StatCard` (KPI con tendencia), `Mm:FormSection` + `Mm:FormNav` + `Mm:ErrorSummary` (formularios largos), `Mm:Toolbar` (filtros GET), `Mm:BarChart` (columnas SVG + tabla), `Mm:NavItem`, `Mm:NavGroup`, `Mm:CommandPalette`, `Mm:EnvRibbon`, `Mm:UserMenu`.
- **Cambios compatibles:** `Mm:Table` (`cards`, `scroll`), `Mm:Field` (`grow`), `Mm:Pagination` (`prevLabel`/`nextLabel`), `Mm:Alert` (`data-controller="mm-alert"` si es `dismissible`).
- **JS (en cada app, no en el paquete):** `mm-shell` (drawer, rail, atajos), `mm-cmdk`, `mm-alert`, `mm-form`, `mm-details`, `mm-dialog`, `mm-confirm`. Admin los trae desde A3.
- **Ojo con los props dentro de bloques:** el contenido de un componente ve los props del componente antes que las variables de la vista (por eso `Mm:FormSection` usa `errorCount` y no `errors`). Las macros importadas en la vista tampoco se ven dentro del bloque: impórtalas ahí.

## Qué NO hace v1.0.0 (pendiente)

- No instala nada en las apps ni toca sus plantillas o CSS; solo existe un `mm-ds.lock` vacío por app.
- ~~Shell de panel~~: llegó en v1.1.0 (A3). Locals (L2) lo adopta con `data-shell="locals"` y sidebar clara (pendiente).
- **JS (Stimulus)** de `mm-sheet`, tabs, alertas y toasts: P5/A2. Los `.twig` ya emiten los `data-*` previstos.
- Alias de migración de tokens viejos (`--mm-weight-*`, `--evt-*`, `--social-*`…): son por app y los crean P1/A1/L1.
- Logo blanco e isotipo son derivados **provisionales**; hay que sustituirlos por los oficiales cuando lleguen.
- Los `.twig` pasan el análisis de sintaxis de Twig 3.24, pero no se han renderizado con el bundle (no está instalado); confirmar `attributes.defaults()` y los bloques contra la versión instalada.
