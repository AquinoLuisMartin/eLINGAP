export default function signaturePad(wire) {
    return {
        strokes: [],
        pointerId: null,
        busy: false,
        saved: false,
        error: '',
        init() {
            this.draw();
        },
        point(event) {
            const canvas = this.$refs.canvas;
            const bounds = canvas.getBoundingClientRect();
            return {
                x: Math.max(0, Math.min(canvas.width, (event.clientX - bounds.left) * canvas.width / bounds.width)),
                y: Math.max(0, Math.min(canvas.height, (event.clientY - bounds.top) * canvas.height / bounds.height)),
            };
        },
        start(event) {
            if (this.busy || this.pointerId !== null || event.button !== 0 || event.isPrimary === false) return;
            this.pointerId = event.pointerId;
            this.$refs.canvas.setPointerCapture(event.pointerId);
            this.strokes.push([this.point(event)]);
            this.saved = false;
            this.error = '';
            this.draw();
        },
        move(event) {
            if (event.pointerId !== this.pointerId) return;
            this.strokes[this.strokes.length - 1].push(this.point(event));
            this.draw();
        },
        stop(event) {
            if (event.pointerId === this.pointerId) this.pointerId = null;
        },
        draw() {
            const canvas = this.$refs.canvas;
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.fillStyle = context.strokeStyle = '#111';
            context.lineWidth = 3;
            context.lineCap = context.lineJoin = 'round';
            for (const stroke of this.strokes) {
                context.beginPath();
                context.arc(stroke[0].x, stroke[0].y, 1.5, 0, Math.PI * 2);
                context.fill();
                context.beginPath();
                context.moveTo(stroke[0].x, stroke[0].y);
                for (const point of stroke.slice(1)) context.lineTo(point.x, point.y);
                context.stroke();
            }
        },
        undo() {
            if (this.busy || this.pointerId !== null) return;
            this.strokes.pop();
            this.saved = false;
            this.draw();
        },
        clear() {
            if (this.busy || this.pointerId !== null) return;
            this.strokes = [];
            this.saved = false;
            this.error = '';
            this.draw();
        },
        async save() {
            if (this.busy || !this.strokes.length || this.pointerId !== null) return;
            this.busy = true;
            this.error = '';
            try {
                const blob = await new Promise(resolve => this.$refs.canvas.toBlob(resolve, 'image/jpeg', 0.92));
                if (!blob || blob.size > 2 * 1024 * 1024) throw new Error('Invalid signature image');
                await new Promise((resolve, reject) => wire.upload('signature', new File([blob], 'signature.jpg', { type: 'image/jpeg' }), resolve, reject));
                if (await wire.saveSignature() !== true) throw new Error('Signature validation failed');
                this.strokes = [];
                this.draw();
                this.saved = true;
            } catch {
                this.error = 'The signature could not be saved. Check the validation messages and try again.';
            } finally {
                this.busy = false;
            }
        },
    };
}
