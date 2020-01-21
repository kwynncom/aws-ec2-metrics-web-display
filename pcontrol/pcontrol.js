function byid(id) { return document.getElementById(id); }

class kwAWSCPU_latest {
    seq;
    resp;

    constructor(seqin) {
	this.seq = seqin;
	const self = this;
        const prld = new Promise(function(resolve) { self.resolveLD = resolve; });
	const praj = new Promise(function(resolve) { self.resolveAJ = resolve; });
	Promise.all([praj, this.prld]).then(function() { self.dispajr(); });		
	window.onload = this.resolveLD;
	
	this.getLatest();
    }
    
    dispajr(resp) {
	
	let a;
	
	try {
	    a = JSON.parse(this.resp);
	} catch (err) { 
	    return; // not worrying about this at the moment; just quit.
	}
	const self = this;
	a.forEach(function(o) {
	    
	    if (o.id === 'filttb' ||
		o.id === 'rawtb') {
		self.doNewRow(o.id, o.v);
		return;
	    }
	    
	    const ele = byid(o.id);
	    if (!ele) return;
	    byid(o.id).innerHTML = o.v;
	});
    }
    
    getLatest() {
	
	if (!this.seq) return;
	const seq1 = parseInt(this.seq);
	if (isNaN(seq1) || seq1 <= 0) return;
		
	const xhr = new XMLHttpRequest();
	
	const self = this;
	xhr.onreadystatechange = function() {
	    if (this.readyState !== 4 || this.status !== 200) return;
	    self.resp = xhr.response;
	    self.resolveAJ();
	}// 
	
	xhr.open('GET', '/../index.php?getLatestOutput=1&seq=' + this.seq + '&XDEBUG_SESSION_START=netbeans-xdebug', true);
	xhr.send();
    }
    
    doNewRow(id, v) {
	const template = document.createElement('template');
	v = v.trim(); // Never return a text node of whitespace as the result
	template.innerHTML = v;	
	if (!template ||
	    !template.content ||
	    !template.content.childNodes
	    ) return;
    
	const pele = byid(id);
    
	template.content.childNodes.forEach(function(child){
	    pele.prepend(child);
	});
	const x = 2;
    }
    
}

// Firefox and private class fields: https://bugzilla.mozilla.org/show_bug.cgi?id=1562054