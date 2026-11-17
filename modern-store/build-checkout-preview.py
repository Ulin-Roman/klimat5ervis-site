"""Build the isolated static checkout preview; no network or CMS calls."""
from pathlib import Path
import base64
import hashlib
import shutil


def csp_hash(text):
    return base64.b64encode(hashlib.sha256(text.encode("utf-8")).digest()).decode("ascii")


base = Path(__file__).resolve().parent
page = (base / "checkout-preview.html").read_text(encoding="utf-8")
css = "\n" + (base / "checkout.css").read_text(encoding="utf-8") + "\n"
script = "\n" + (base / "checkout.js").read_text(encoding="utf-8") + "\n"
assert "</script" not in script.lower()
standalone = page.replace('<link rel="stylesheet" href="checkout.css">', "<style>" + css + "</style>")
standalone = standalone.replace('<script src="checkout.js" defer></script>', "")
# The external source uses defer; an inline classic script must run after the DOM.
standalone = standalone.replace("</body>", "<script>" + script + "</script>\n</body>")
standalone = standalone.replace("script-src 'self'", "script-src 'sha256-" + csp_hash(script) + "'")
standalone = standalone.replace("style-src 'self'", "style-src 'sha256-" + csp_hash(css) + "'")
(base / "checkout-preview-standalone.html").write_text(standalone, encoding="utf-8")
dist = base / "dist"
dist.mkdir(exist_ok=True)
for source, target in [("checkout-preview.html", "index.html"), ("checkout.css", "checkout.css"),
                       ("checkout.js", "checkout.js"), ("checkout-preview-standalone.html", "standalone.html")]:
    shutil.copyfile(base / source, dist / target)
print("Built external-asset and standalone previews; both block connections and form submission.")
