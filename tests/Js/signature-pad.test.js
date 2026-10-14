import assert from 'node:assert/strict';
import test from 'node:test';
import signaturePad from '../../resources/js/signature-pad.js';

function pad(wire) {
    const context = {
        fillRect() {}, beginPath() {}, arc() {}, fill() {}, moveTo() {}, lineTo() {}, stroke() {},
    };
    const instance = signaturePad(wire);
    instance.$refs = { canvas: {
        width: 900, height: 300,
        getContext: () => context,
        getBoundingClientRect: () => ({ left: 0, top: 0, width: 450, height: 150 }),
        setPointerCapture() {},
        toBlob: callback => callback(new Blob(['synthetic-signature'], { type: 'image/jpeg' })),
    } };
    instance.init();
    return instance;
}

function draw(instance) {
    instance.start({ pointerId: 1, button: 0, isPrimary: true, clientX: 30, clientY: 40 });
    instance.move({ pointerId: 1, clientX: 80, clientY: 60 });
    instance.stop({ pointerId: 1 });
}

test('blank, cleared and undone signatures cannot be uploaded', async () => {
    let uploads = 0;
    const instance = pad({ upload: () => uploads++ });
    await instance.save();
    draw(instance);
    instance.undo();
    await instance.save();
    draw(instance);
    instance.clear();
    await instance.save();
    assert.equal(uploads, 0);
});

test('saving sends a JPEG through the existing signature action', async () => {
    let uploaded;
    let saves = 0;
    const instance = pad({
        upload(property, file, resolve) { uploaded = { property, file }; resolve(); },
        async saveSignature() { saves++; return true; },
    });
    draw(instance);
    await instance.save();
    assert.equal(uploaded.property, 'signature');
    assert.equal(uploaded.file.type, 'image/jpeg');
    assert.equal(uploaded.file.name, 'signature.jpg');
    assert.equal(saves, 1);
    assert.equal(instance.saved, true);
    await instance.save();
    assert.equal(saves, 1);
});

test('a failed save keeps the signature available for retry', async () => {
    let saves = 0;
    const instance = pad({
        upload(property, file, resolve) { resolve(); },
        async saveSignature() { saves++; return saves > 1; },
    });
    draw(instance);
    await instance.save();
    assert.notEqual(instance.error, '');
    assert.equal(instance.busy, false);
    assert.equal(instance.saved, false);
    await instance.save();
    assert.equal(saves, 2);
    assert.equal(instance.saved, true);
});

test('an active stroke cannot be uploaded until the pointer is released', async () => {
    let uploads = 0;
    const instance = pad({
        upload(property, file, resolve) { uploads++; resolve(); },
        async saveSignature() { return true; },
    });
    instance.start({ pointerId: 1, button: 0, isPrimary: true, clientX: 30, clientY: 40 });
    await instance.save();
    assert.equal(uploads, 0);
    instance.stop({ pointerId: 1 });
    await instance.save();
    assert.equal(uploads, 1);
});
