export default function idImageEditor(wire, property, square, saveAction) {
    return {
        square, ready: false, busy: false, uploaded: false, error: '', needsCrop: false,
        zoom: 1, positionX: 0.5, positionY: 0.5, image: null, original: null,
        async loadSaved(dataUri) {
            try {
                const blob = await (await fetch(dataUri)).blob();
                await this.load({ target: { files: [new File([blob], 'saved-photo', { type: blob.type })] } });
            } catch {
                this.error = 'The saved photo could not be opened. Upload the original image again.';
            }
        },
        async load(event) {
            this.error = '';
            this.uploaded = false;
            this.ready = false;
            const file = event.target.files?.[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 2 * 1024 * 1024) {
                this.error = 'Choose a JPEG or PNG image up to 2MB.';
                return;
            }
            const url = URL.createObjectURL(file);
            try {
                const image = new Image();
                image.src = url;
                await image.decode();
                if (image.naturalWidth > 6000 || image.naturalHeight > 6000) {
                    this.error = 'Choose an image no larger than 6000 pixels on either side.';
                    return;
                }
                this.original = file;
                this.image = image;
                this.needsCrop = image.naturalWidth !== image.naturalHeight;
                this.zoom = 1;
                this.positionX = this.positionY = 0.5;
                this.ready = true;
                await this.$nextTick();
                this.draw();
            } catch {
                this.error = 'This image could not be opened. Choose another JPEG or PNG.';
            } finally {
                URL.revokeObjectURL(url);
            }
        },
        draw() {
            const canvas = this.$refs.canvas;
            if (!this.image || !canvas) return;
            const width = this.image.naturalWidth;
            const height = this.image.naturalHeight;
            if (this.square) {
                canvas.width = canvas.height = 600;
                const crop = Math.min(width, height) / this.zoom;
                const context = canvas.getContext('2d');
                context.fillStyle = '#fff';
                context.fillRect(0, 0, 600, 600);
                context.drawImage(this.image, (width - crop) * this.positionX, (height - crop) * this.positionY, crop, crop, 0, 0, 600, 600);
            } else {
                const scale = Math.min(1, 1600 / Math.max(width, height));
                canvas.width = Math.round(width * scale);
                canvas.height = Math.round(height * scale);
                const context = canvas.getContext('2d');
                context.fillStyle = '#fff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(this.image, 0, 0, canvas.width, canvas.height);
            }
        },
        async upload() {
            if (this.busy || !this.ready) return;
            this.busy = true;
            this.error = '';
            try {
                const blob = await new Promise(resolve => this.$refs.canvas.toBlob(resolve, 'image/jpeg', 0.92));
                if (!blob || blob.size > 2 * 1024 * 1024) throw new Error('size');
                const upload = (name, file) => new Promise((resolve, reject) => wire.upload(name, file, resolve, reject));
                if (this.square) await upload('photoOriginal', this.original);
                await upload(property, new File([blob], `${property}.jpg`, { type: 'image/jpeg' }));
                if (saveAction && await wire[saveAction]() !== true) throw new Error('validation');
                this.uploaded = true;
            } catch {
                this.error = 'The image could not be saved. Check the validation messages and try again.';
            } finally {
                this.busy = false;
            }
        },
    };
}
