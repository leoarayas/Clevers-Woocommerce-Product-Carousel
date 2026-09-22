(function (wp) {
  if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components || !wp.data) {
    return;
  }

  var registerBlockType = wp.blocks.registerBlockType;
  var __ = wp.i18n.__;
  var createElement = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useSelect = wp.data.useSelect;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var Placeholder = wp.components.Placeholder;
  var Spinner = wp.components.Spinner;
  var Notice = wp.components.Notice;

  function Edit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var carouselId = Number(attributes.carouselId || 0);

    var data = useSelect(function (select) {
      var core = select("core");
      var records = core.getEntityRecords("postType", "cleverspr_carousel", {
        per_page: -1,
        status: "publish,draft,pending,private"
      });
      var isResolving = core.isResolving("getEntityRecords", [
        "postType",
        "cleverspr_carousel",
        { per_page: -1, status: "publish,draft,pending,private" }
      ]);

      return {
        records: records,
        isResolving: isResolving
      };
    }, []);

    var options = [{ label: __("Select a carousel", "clevers-product-carousel"), value: 0 }];
    if (Array.isArray(data.records)) {
      data.records.forEach(function (item) {
        var label = (item && item.title && item.title.rendered) ? item.title.rendered : ("#" + item.id);
        options.push({ label: label + " (#" + item.id + ")", value: Number(item.id) });
      });
    }

    return createElement(
      Fragment,
      null,
      createElement(
        InspectorControls,
        null,
        createElement(
          PanelBody,
          { title: __("Carousel Settings", "clevers-product-carousel"), initialOpen: true },
          createElement(SelectControl, {
            label: __("Carousel", "clevers-product-carousel"),
            value: carouselId,
            options: options,
            onChange: function (value) {
              setAttributes({ carouselId: Number(value || 0) });
            }
          })
        )
      ),
      createElement(
        Placeholder,
        {
          label: __("Clevers Product Carousel", "clevers-product-carousel"),
          instructions: __("Choose a saved carousel to render on the frontend.", "clevers-product-carousel")
        },
        data.isResolving ? createElement(Spinner) : null,
        !data.isResolving && !Array.isArray(data.records)
          ? createElement(
              Notice,
              { status: "warning", isDismissible: false },
              __("Unable to load carousels. Verify the CPT is available in REST.", "clevers-product-carousel")
            )
          : null,
        createElement(SelectControl, {
          label: __("Carousel", "clevers-product-carousel"),
          value: carouselId,
          options: options,
          onChange: function (value) {
            setAttributes({ carouselId: Number(value || 0) });
          }
        }),
        carouselId > 0
          ? createElement("p", null, __("Selected carousel ID:", "clevers-product-carousel") + " " + carouselId)
          : createElement("p", null, __("No carousel selected yet.", "clevers-product-carousel"))
      )
    );
  }

  registerBlockType("cleverspr/carousel", {
    apiVersion: 2,
    title: __("Clevers Product Carousel", "clevers-product-carousel"),
    icon: "images-alt2",
    category: "widgets",
    attributes: {
      carouselId: {
        type: "number",
        default: 0
      }
    },
    edit: Edit,
    save: function () {
      return null;
    }
  });
})(window.wp);
