import { __ } from "@wordpress/i18n";
import { useState } from "@wordpress/element";
import { safeUrl } from "./safeUrl";

import Items from "./items";
import Loading from "./loading";
import Search from "./search";

import {
  FILTER_LICENSES,
  FILTER_LICENSE_TYPE,
  FILTER_CATEGORY,
  FILTER_EXTENSION,
  FILTER_ASPECT_RATIO,
  FILTER_SIZE,
} from "../constants";
import NoData from "./noData";

export default function SeachModal(props) {
  const {
    data,
    loading,
    openverseData,
    q,
    setQ,
    filterData,
    setFilterData,
    setOpenverseImage,
    attributes,
    setAttributes,
    pagination,
    setPagination,
    totalPages,
    openverseError,
    setOpenverseModal,
    openverseDataFetch,
    openverseDataCount,
  } = props;

  const { searchQ } = attributes;

  const handleChange = (e) => {
    // Destructuring
    const { value, checked, name } = e.target;
    const { licenses, licenseType, category, extension, aspectRatio, size } =
      filterData;

    // Case 1 : The user checks the box
    if (checked) {
      switch (name) {
        case "licenses":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses, value],
            category: [...category],
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "licenseType":
          setFilterData({
            licenseType: [...licenseType, value],
            licenses: [...licenses],
            category: [...category],
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "category":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: [...category, value],
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "extension":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: [...category],
            extension: [...extension, value],
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "aspectRatio":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: [...category],
            extension: [...extension],
            aspectRatio: [...aspectRatio, value],
            size: [...size],
          });
          break;
        case "size":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: [...category],
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: [...size, value],
          });
          break;

        default:
          return false;
      }
    }

    // Case 2  : The user unchecks the box
    else {
      switch (name) {
        case "licenses":
          setFilterData({
            licenseType: [...licenseType],
            licenses: licenses.filter((e) => e !== value),
            category: [...category],
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "licenseType":
          setFilterData({
            licenseType: licenseType.filter((e) => e !== value),
            licenses: [...licenses],
            category: [...category],
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "category":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: category.filter((e) => e !== value),
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "extension":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: [...category],
            extension: extension.filter((e) => e !== value),
            aspectRatio: [...aspectRatio],
            size: [...size],
          });
          break;
        case "aspectRatio":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: [...category],
            extension: [...extension],
            aspectRatio: aspectRatio.filter((e) => e !== value),
            size: [...size],
          });
          break;
        case "size":
          setFilterData({
            licenseType: [...licenseType],
            licenses: [...licenses],
            category: [...category],
            extension: [...extension],
            aspectRatio: [...aspectRatio],
            size: size.filter((e) => e !== value),
          });
          break;

        default:
          return false;
      }
    }
  };

  // image upload
  const [selectItem, setSelectItem] = useState(null);

  const uploadImage = (e) => {
    if (selectItem) {
      let data = new FormData();
      data.append("action", "eb_get_item");
      data.append(
        "openverse_item_nonce",
        EBOpenVerseLocalize.openverse_item_nonce
      );

      data.append("image_url", selectItem.url);

      fetch(EBOpenVerseLocalize.ajax_url, {
        method: "POST",
        body: data,
      }) // wrapped
        .then((res) => {
          if (!res.ok) {
            throw new Error(res.statusText);
          }
          return res.text();
        })
        .then((data) => {
          setAttributes({
            imageurl: data,
            imageAttr: {
              title: selectItem.title,
              foreignUrl: safeUrl(selectItem.foreign_landing_url) || "",
              creator: selectItem.creator,
              creatorUrl: safeUrl(selectItem.creator_url) || "",
              license: selectItem.license,
              licenseUrl: safeUrl(selectItem.license_url) || "",
              licenseVersion: selectItem.license_version,
            },
          });
          setOpenverseImage(data);
          props.setTrigger(false);
        })
        .catch((err) => console.log(err));
    }
  };

  const [limit, setLimit] = useState(12);
  const [loadmoreClick, setLoadmoreClick] = useState(false);

  // load more
  const loadMore = () => {
    setLoadmoreClick(true);
    if (pagination < totalPages) {
      setPagination((pagination) => pagination + 1);
      setLimit((limit) => limit + 12);
    } else {
      console.log("Load more not working");
    }
  };

  return props.trigger ? (
    <div className="openverse-modal">
      <div className="openverse-modal__inner">
        <div className="openverse-modal__header">
          <div className="title-section">
            {__("Select Media", "eb-openverse-block")}
          </div>
          <button className="close-btn" onClick={() => props.setTrigger(false)}>
            <span class="dashicons dashicons-no"></span>
          </button>
        </div>
        <div className="openverse-modal__content">
          <Search
            setOpenverseModal={setOpenverseModal}
            openverseDataFetch={openverseDataFetch}
            openverseDataCount={openverseDataCount}
            loading={loading}
            searchQ={searchQ}
            setAttributes={setAttributes}
            componentClassName="search-section openverse-search-section"
          ></Search>
          <div className="search-result-section">
            <div className="search-content">
              <p className="search-key">
                <span>{__("Search Key:", "eb-openverse-block")} </span>
                {searchQ}
              </p>

              {loading && <Loading limit={limit} />}

              {!loading && (
                <>
                  {openverseError.status && (
                    <span className="openverse-error">
                      <NoData></NoData>
                    </span>
                  )}

                  {!openverseError.status && (
                    <Items
                      data={openverseData}
                      selectItem={selectItem}
                      setSelectItem={setSelectItem}
                    />
                  )}
                </>
              )}

              {totalPages > pagination && (
                <button className="loadmore-btn" onClick={loadMore}>
                  {loading
                    ? __("Loading ...", "eb-openverse-block")
                    : __("Load More", "eb-openverse-block")}

                  {}
                </button>
              )}
            </div>
            <div className="search-filter">
              <h4>{__("FILTER BY", "eb-openverse-block")}</h4>

              <form className="filters-form">
                <div className="filter-item">
                  <h5>{__("LICENSES", "eb-openverse-block")}</h5>
                  {FILTER_LICENSES.map(({ icon, label, value }, index) => {
                    return (
                      <div className="filter-item-inner" key={index}>
                        <input
                          className="form-check-input"
                          type="checkbox"
                          name="licenses"
                          value={value}
                          id={`licenses-${index}`}
                          onChange={handleChange}
                        />
                        <label
                          className="form-check-label"
                          htmlFor={`licenses-${index}`}
                        >
                          {icon.map((item) => item)}
                          {label}
                        </label>
                      </div>
                    );
                  })}
                </div>

                <div className="filter-item">
                  <h5>{__("LICENSE TYPE", "eb-openverse-block")}</h5>
                  {FILTER_LICENSE_TYPE.map(({ label, value }, index) => {
                    return (
                      <div className="filter-item-inner" key={index}>
                        <input
                          className="form-check-input"
                          type="checkbox"
                          name="licenseType"
                          value={value}
                          id={`licenseType-${index}`}
                          onChange={handleChange}
                        />
                        <label
                          className="form-check-label"
                          htmlFor={`licenseType-${index}`}
                        >
                          {label}
                        </label>
                      </div>
                    );
                  })}
                </div>

                <div className="filter-item">
                  <h5>{__("Image Type", "eb-openverse-block")}</h5>
                  {FILTER_CATEGORY.map(({ label, value }, index) => {
                    return (
                      <div className="filter-item-inner" key={index}>
                        <input
                          className="form-check-input"
                          type="checkbox"
                          name="category"
                          value={value}
                          id={`category-${index}`}
                          onChange={handleChange}
                        />
                        <label
                          className="form-check-label"
                          htmlFor={`category-${index}`}
                        >
                          {label}
                        </label>
                      </div>
                    );
                  })}
                </div>

                <div className="filter-item">
                  <h5>{__("Extension", "eb-openverse-block")}</h5>
                  {FILTER_EXTENSION.map(({ label, value }, index) => {
                    return (
                      <div className="filter-item-inner" key={index}>
                        <input
                          className="form-check-input"
                          type="checkbox"
                          name="extension"
                          value={value}
                          id={`extension-${index}`}
                          onChange={handleChange}
                        />
                        <label
                          className="form-check-label"
                          htmlFor={`extension-${index}`}
                        >
                          {label}
                        </label>
                      </div>
                    );
                  })}
                </div>

                <div className="filter-item">
                  <h5>{__("Aspect ratio", "eb-openverse-block")}</h5>
                  {FILTER_ASPECT_RATIO.map(({ label, value }, index) => {
                    return (
                      <div className="filter-item-inner" key={index}>
                        <input
                          className="form-check-input"
                          type="checkbox"
                          name="aspectRatio"
                          value={value}
                          id={`aspectRatio-${index}`}
                          onChange={handleChange}
                        />
                        <label
                          className="form-check-label"
                          htmlFor={`aspectRatio-${index}`}
                        >
                          {label}
                        </label>
                      </div>
                    );
                  })}
                </div>

                <div className="filter-item">
                  <h5>{__("Image Size", "eb-openverse-block")}</h5>
                  {FILTER_SIZE.map(({ label, value }, index) => {
                    return (
                      <div className="filter-item-inner" key={index}>
                        <input
                          className="form-check-input"
                          type="checkbox"
                          name="size"
                          value={value}
                          id={`size-${index}`}
                          onChange={handleChange}
                        />
                        <label
                          className="form-check-label"
                          htmlFor={`size-${index}`}
                        >
                          {label}
                        </label>
                      </div>
                    );
                  })}
                </div>
              </form>
            </div>
          </div>
        </div>

        <div className="openverse-modal__footer">
          <button
            className="select-btn"
            disabled={!selectItem ? true : false}
            onClick={uploadImage}
          >
            {__("Select", "eb-openverse-block")}
          </button>
        </div>
      </div>
    </div>
  ) : (
    ""
  );
}
