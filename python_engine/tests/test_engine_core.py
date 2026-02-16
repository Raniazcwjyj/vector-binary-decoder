from python_engine.engine_core import build_palette_map, merge_parsed_data


def test_build_palette_map_reads_user_palette():
    msg = {
        "body": {
            "userPalette": {
                "colors": [
                    {"index": 0, "css": "#ffffff", "opacity": 1},
                    {"index": 1, "css": "#00923d", "opacity": 0.8},
                ]
            }
        }
    }
    palette = build_palette_map(msg)
    assert palette[0]["css"] == "#ffffff"
    assert palette[1]["opacity"] == 0.8


def test_merge_parsed_data_sorts_shape_loop_index():
    a = {"shapes": [{"index": 2}], "loops": [{"index": 3}], "interfaces": []}
    b = {"shapes": [{"index": 1}], "loops": [{"index": 2}], "interfaces": []}
    merged = merge_parsed_data([a, b])
    assert [s["index"] for s in merged["shapes"]] == [1, 2]
    assert [l["index"] for l in merged["loops"]] == [2, 3]
