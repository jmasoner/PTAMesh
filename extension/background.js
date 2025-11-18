// Sends payload to WordPress REST endpoint
const WP_ENDPOINT = 'https://YOURDOMAIN.com/wp-json/ptamesh/v1/add-product';

chrome.runtime.onMessage.addListener(async (msg, sender, sendResponse) => {
  if (msg.type === 'PTAMESH_ADD') {
    try {
      const res = await fetch(WP_ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
          // For Day-1, rely on logged-in cookie; later add token/JWT
        },
        body: JSON.stringify(msg.payload),
        credentials: 'include'
      });
      const data = await res.json();
      if (data.ok) {
        chrome.notifications?.create({
          type: 'basic',
          iconUrl: 'icon.png',
          title: 'PTAMesh',
          message: `Added: ${msg.payload.title}`
        });
      } else {
        console.error('PTAMesh error', data);
        alert('PTAMesh error: ' + (data.error || 'Unknown'));
      }
    } catch (e) {
      console.error('PTAMesh network error', e);
      alert('Network error');
    }
  }
});
