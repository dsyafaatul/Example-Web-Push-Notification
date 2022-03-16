const PUBLICKEY = 'BNKKKP0gA1iRjpWEr4LWC04d005sGnrEUYcgcV6g2IXwVBuzNiWDgXyvL9vUyTb33C4SOEfElh0lcBFGkKVZZ1I';

function urlB64ToUint8Array(base64String) {
	const padding = '='.repeat((4 - base64String.length % 4) % 4);
	const base64 = (base64String + padding)
	  .replace(/\-/g, '+')
	  .replace(/_/g, '/');
  
	const rawData = atob(base64);
	const outputArray = new Uint8Array(rawData.length);
  
	for (let i = 0; i < rawData.length; ++i) {
	  outputArray[i] = rawData.charCodeAt(i);
	}
	return outputArray;
  }

self.addEventListener('activate', function(){
	console.log('ServiceWorker : activate event');
	try {
		var publickey = urlB64ToUint8Array(PUBLICKEY);
		var opt = {
			applicationServerKey: publickey,
			userVisibleOnly: true
		};
		self.registration.pushManager.subscribe(opt).then(function(sub){
			fetch('server.php?action=subscribe', {
				'method': 'POST',
				'headers': {
					'Content-Type': 'application/json'
				},
				'body': JSON.stringify(sub)
			}).then(function(res){
				console.log(res.statusText);
			})
		});
	} catch (e) {
		console.log('Error Subscribing notifications: '+e);
	}
});

self.addEventListener('push', function(e){
	console.log('ServiceWorker: push event');
	if(e.data){
		if(notif = e.data.json()){
			e.waitUntil(self.registration.showNotification(notif.title, {
				actions: notif.actions,
				body: notif.body,
				icon: notif.icon
			}))
		}
	}
});

self.addEventListener('notificationclick', function(e){
	console.log('notificationclick event: ');
});