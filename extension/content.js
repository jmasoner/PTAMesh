// Inject "Add to Inventory" button on Amazon product pages
(function(){
  function findASIN() {
    const urlMatch = location.pathname.match(/\/dp\/([A-Z0-9]{10})/i);
    if (urlMatch) return urlMatch[1];
    const asinEl = document.querySelector('#ASIN, input[name="ASIN"]');
    if (asinEl && asinEl.value) return asinEl.value.trim();
    return null;
  }

  function getTitle() {
    const el = document.getElementById('productTitle') || document.querySelector('[data-asin-title]');
    return el ? el.textContent.trim() : document.title.replace(/Amazon\.com:\s*/, '');
  }

  function getImage() {
    const img = document.querySelector('#imgTagWrapperId img, img[data-old-hires], img[src*="images"]');
    return img ? img.src : '';
  }

  function getPrice() {
    const el = document.querySelector('#corePrice_feature_div .a-offscreen, .a-price .a-offscreen');
    return el ? el.textContent.replace(/[^0-9.]/g, '') : '';
  }

  function injectButton() {
    if (document.getElementById('ptamesh-add')) return;
    const target = document.querySelector('#add-to-cart-button, #buy-now-button') || document.querySelector('#productTitle');
    if (!target) return;
    const btn = document.createElement('button');
    btn.id = 'ptamesh-add';
    btn.textContent = 'Add to PTAMesh Inventory';
    btn.className = 'ptamesh-btn';
    btn.onclick = async function(){
      const asin = findASIN();
      if (!asin) { alert('ASIN not found'); return; }
      const payload = {
        asin,
        title: getTitle(),
        url: location.href,
        image: getImage(),
        price: getPrice(),
        qty: 1
      };
      chrome.runtime.sendMessage({ type: 'PTAMESH_ADD', payload });
    };
    target.parentNode.insertBefore(btn, target.nextSibling);
  }

  document.addEventListener('DOMContentLoaded', injectButton);
  setTimeout(injectButton, 1500);
})();
