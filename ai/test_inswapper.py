import insightface, cv2, numpy as np
m = insightface.model_zoo.get_model('c:/laragon/www/NeuroMask/ai/inswapper_128.onnx')
img = np.zeros((500,500,3),dtype=np.uint8)
class F: pass
f = F()
f.kps = np.array([[10,10],[20,10],[15,15],[10,20],[20,20]])
f.bbox = np.array([5,5,25,25])
f.normed_embedding = np.zeros(512)
res = m.get(img, f, f, paste_back=False)
print(type(res))
if isinstance(res, tuple):
    print(len(res))
else:
    print(res.shape)
