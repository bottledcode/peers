function read(id, text) {
    const quill = new Quill('#' + id, {
        theme: 'snow',
        readOnly: true,
    });
    quill.setContents(JSON.parse(text));
}
