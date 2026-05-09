import * as THREE from "https://unpkg.com/three@0.160.0/build/three.module.js";

const roots = document.querySelectorAll("[data-three-scene]");

roots.forEach((root) => {
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    const accent = root.dataset.accent || "#2563eb";
    const accent2 = root.dataset.accent2 || "#14b8a6";
    const objects = [];

    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x000000, 0);
    root.appendChild(renderer.domElement);

    camera.position.set(0, 1.1, 7.2);

    scene.add(new THREE.AmbientLight(0xffffff, 1.2));

    const keyLight = new THREE.DirectionalLight(0xffffff, 2);
    keyLight.position.set(4, 6, 4);
    scene.add(keyLight);

    const rimLight = new THREE.PointLight(new THREE.Color(accent2), 55, 16);
    rimLight.position.set(-4, 2, 2);
    scene.add(rimLight);

    const primary = new THREE.Color(accent);
    const secondary = new THREE.Color(accent2);

    const glassMaterial = new THREE.MeshPhysicalMaterial({
        color: primary,
        metalness: 0.18,
        roughness: 0.24,
        transmission: 0.25,
        thickness: 0.8,
        transparent: true,
        opacity: 0.72,
    });

    const edgeMaterial = new THREE.LineBasicMaterial({
        color: 0xffffff,
        transparent: true,
        opacity: 0.34,
    });

    function addBlock(x, y, z, w, h, d, color = primary) {
        const geometry = new THREE.BoxGeometry(w, h, d);
        const material = glassMaterial.clone();
        material.color = color;
        const mesh = new THREE.Mesh(geometry, material);
        mesh.position.set(x, y, z);
        scene.add(mesh);
        objects.push(mesh);

        const edges = new THREE.LineSegments(new THREE.EdgesGeometry(geometry), edgeMaterial);
        edges.position.copy(mesh.position);
        scene.add(edges);
        objects.push(edges);

        return mesh;
    }

    const floor = new THREE.GridHelper(9, 18, secondary, new THREE.Color("#334155"));
    floor.position.y = -1.55;
    floor.material.transparent = true;
    floor.material.opacity = 0.28;
    scene.add(floor);

    const layout = root.dataset.scene || "default";
    if (layout === "housing") {
        addBlock(-2.55, -0.62, 0.1, 1.05, 1.85, 1.05, primary);
        addBlock(-1.35, -0.28, -0.35, 1.05, 2.55, 1.05, secondary);
        addBlock(-0.08, -0.76, 0.22, 1.22, 1.55, 1.22, primary.clone().lerp(secondary, 0.44));
        addBlock(1.38, -0.42, -0.28, 1.16, 2.28, 1.16, secondary.clone().lerp(new THREE.Color("#ffffff"), 0.18));
        addBlock(2.66, -0.88, 0.18, 0.96, 1.28, 0.96, primary.clone().lerp(new THREE.Color("#ffffff"), 0.16));

        for (let i = 0; i < 7; i += 1) {
            const x = -3.1 + i * 1.05;
            const z = -1.65 - (i % 2) * 0.28;
            addBlock(x, -1.02, z, 0.42, 0.72 + (i % 3) * 0.22, 0.42, i % 2 ? secondary : primary);
        }
    } else {
        addBlock(-2.1, -0.35, 0, 1.15, 1.9, 1.15, primary);
        addBlock(-0.55, -0.1, -0.3, 1.25, 2.4, 1.25, secondary);
        addBlock(1.15, -0.55, 0.15, 1.35, 1.5, 1.35, primary.clone().lerp(secondary, 0.38));
        addBlock(2.55, -0.2, -0.6, 0.85, 2.15, 0.85, secondary.clone().lerp(new THREE.Color("#ffffff"), 0.22));
    }

    const ring = new THREE.Mesh(
        new THREE.TorusGeometry(1.42, 0.018, 12, 120),
        new THREE.MeshBasicMaterial({ color: secondary, transparent: true, opacity: 0.64 })
    );
    ring.rotation.x = Math.PI / 2.6;
    ring.position.set(0.15, -0.18, -0.3);
    scene.add(ring);
    objects.push(ring);

    const pointsGeometry = new THREE.BufferGeometry();
    const pointCount = 130;
    const positions = new Float32Array(pointCount * 3);
    for (let i = 0; i < pointCount; i += 1) {
        positions[i * 3] = (Math.random() - 0.5) * 9;
        positions[i * 3 + 1] = (Math.random() - 0.5) * 5;
        positions[i * 3 + 2] = (Math.random() - 0.5) * 5;
    }
    pointsGeometry.setAttribute("position", new THREE.BufferAttribute(positions, 3));
    const points = new THREE.Points(
        pointsGeometry,
        new THREE.PointsMaterial({ color: 0xffffff, size: 0.025, transparent: true, opacity: 0.72 })
    );
    scene.add(points);

    function resize() {
        const rect = root.getBoundingClientRect();
        const width = Math.max(1, rect.width);
        const height = Math.max(1, rect.height);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
    }

    let pointerX = 0;
    let pointerY = 0;

    root.addEventListener("pointermove", (event) => {
        const rect = root.getBoundingClientRect();
        pointerX = ((event.clientX - rect.left) / rect.width - 0.5) * 0.5;
        pointerY = ((event.clientY - rect.top) / rect.height - 0.5) * 0.35;
    });

    function animate(time) {
        const t = time * 0.001;
        scene.rotation.y = Math.sin(t * 0.24) * 0.14 + pointerX;
        scene.rotation.x = -0.08 + pointerY;
        ring.rotation.z = t * 0.25;
        points.rotation.y = t * 0.035;

        objects.forEach((object, index) => {
            object.position.y += Math.sin(t + index) * 0.0009;
        });

        renderer.render(scene, camera);
        window.requestAnimationFrame(animate);
    }

    resize();
    window.addEventListener("resize", resize);
    window.requestAnimationFrame(animate);
});
